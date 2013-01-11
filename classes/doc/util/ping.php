<?php

/*
 * Copyright (c) 2011 by Jeff Geerling.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies 
 * of the Software, and to permit persons to whom the Software is furnished to do 
 * so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*
 * Originally pulled from github on 1/11/2013 (https://github.com/geerlingguy/Ping).
 * 
 * Modified by Jason Orrill <jason_orrill@brown.edu> as follows:
 * - Renamed to DOC_Util_Ping for use within the DOC module.
 * - Created constants for each of the ping method types.
 * - Created static method "do_ping" to use as a convenience method.
 * 
 */

/**
 * Ping for PHP.
 *
 * This class pings a host.
 *
 * The ping() method pings a server using 'exec', 'socket', or 'fsockopen', and
 * and returns FALSE if the server is unreachable within the given ttl/timeout,
 * or the latency in milliseconds if the server is reachable.
 *
 * Example usage:
 * @code
 *   $ping = new Ping('www.example.com');
 *   $latency = $ping->ping();
 * @endcode
 *
 * @version 1.0-beta1
 * @author Jeff Geerling.
 */
class DOC_Util_Ping {

	private $host;
	private $ttl;
	private $data = 'Ping';

	const METHOD_EXEC = 'exec' ;
	const METHOD_FSOCKOPEN = 'fsockopen' ;
	const METHOD_SOCKET = 'socket' ;
	
	/**
	 * 
	 * @param string $host
	 * @param string $method
	 * @param int $ttl
	 * @return mixed
	 */
	public static function do_ping($host, $method = self::METHOD_EXEC, $ttl = 255) {
		$ping = new DOC_Util_Ping($host, $ttl) ;
		return $ping->ping($method) ;
	}
	
	
	/**
	 * Called when the Ping object is created.
	 *
	 * @param $host (string)
	 *   The host to be pinged.
	 * @param $ttl (int)
	 *   Time-to-live (TTL) (You may get a 'Time to live exceeded' error if this
	 *   value is set too low. The TTL value indicates the scope or range in which
	 *   a packet may be forwarded. By convention:
	 *     - 0 = same host
	 *     - 1 = same subnet
	 *     - 32 = same site
	 *     - 64 = same region
	 *     - 128 = same continent
	 *     - 255 = unrestricted
	 *   The TTL is also used as a general 'timeout' value for fsockopen(), so if
	 *   you are using that method, you might want to set a default of 5-10 sec to
	 *   avoid blocking network connections.
	 *
	 * @return (empty)
	 */
	public function __construct($host, $ttl = 255) {
		if (!isset($host)) {
			throw new Exception("Error: Host name not supplied.");
		}

		$this->host = $host;
		$this->ttl = $ttl;
	}

	/**
	 * Set the ttl (in hops).
	 *
	 * @param $ttl (int)
	 *   TTL in hops.
	 *
	 * @return (empty)
	 */
	public function setTtl($ttl) {
		$this->ttl = $ttl;
	}

	/**
	 * Set the host.
	 *
	 * @param $host (string)
	 *   Host name or IP address.
	 *
	 * @return (empty)
	 */
	public function setHost($host) {
		$this->host = $host;
	}

	/**
	 * Ping a host.
	 *
	 * @param $method (string)
	 *   Method to use when pinging:
	 *     - exec (default): Pings through the system ping command. Fast and
	 *       robust, but a security risk if you pass through user-submitted data.
	 *     - fsockopen: Pings a server on port 80.
	 *     - socket: Creates a RAW network socket. Only usable in some
	 *       environments, as creating a SOCK_RAW socket requires root privileges.
	 *
	 * @return (mixed)
	 *   Latency as integer, in ms, if host is reachable or FALSE if host is down.
	 */
	public function ping($method = self::METHOD_EXEC) {
		$latency = false;

		switch ($method) {
			// The exec method uses the possibly insecure exec() function, which
			// passes the input to the system. This is potentially VERY dangerous if
			// you pass in any user-submitted data. Be SURE you sanitize your inputs!
			case self::METHOD_EXEC:
				$ttl = escapeshellcmd($this->ttl);
				$host = escapeshellcmd($this->host);
				// -n = numeric output; -c = number of pings; -t = ttl.
				$str = exec('ping -n -c 1 -t ' . $ttl . ' ' . $host, $output, $return);
				// Second output line contains result of ping. Parse if not empty.
				if (!empty($output[1])) {
					$array = explode(' ', $output[1]);
					// Remove 'time=' from string.
					$latency = str_replace('time=', '', $array[6]);
					// Convert latency to microseconds.
					$latency = round($latency);
				} else {
					$latency = false;
				}
				break;

			// The fsockopen method simply tries to reach the host on port 80. This
			// method is often the fastest, but not necessarily the most reliable.
			// Even if a host doesn't respond, fsockopen may still make a connection.
			case self::METHOD_FSOCKOPEN:
				$start = microtime(true);
				$fp = fsockopen($this->host, 80, $errno, $errstr, $this->ttl);
				if (!$fp) {
					$latency = false;
				} else {
					$latency = microtime(true) - $start;
					$latency = round($latency * 1000);
				}
				break;

			// The socket method uses raw network packet data to try sending an ICMP
			// ping packet to a server, then measures the response time. Using this
			// method requires the script to be run with root privileges, though, so
			// this method only works reliably on Windows systems and on Linux servers
			// where the script is not being run as a web user.
			case self::METHOD_SOCKET:
				// Create a package.
				$type = "\x08";
				$code = "\x00";
				$checksum = "\x00\x00";
				$identifier = "\x00\x00";
				$seqNumber = "\x00\x00";
				$package = $type . $code . $checksum . $identifier . $seqNumber . $this->data;

				// Calculate the checksum.
				$checksum = $this->calculateChecksum($package); // Calculate the checksum.
				// Finalize the package.
				$package = $type . $code . $checksum . $identifier . $seqNumber . $this->data;

				// Create a socket, connect to server, then read the socket and calculate.
				if ($socket = socket_create(AF_INET, SOCK_RAW, 1)) {
					socket_connect($socket, $this->host, null);
					$start = microtime(true);
					// Send the package.
					socket_send($socket, $package, strlen($package), 0);
					if (socket_read($socket, 255) !== false) {
						$latency = microtime(true) - $start;
						$latency = round($latency * 1000);
					} else {
						$latency = false;
					}
					// Close the socket.
					socket_close($socket);
				} else {
					$latency = false;
				}
				// Close the socket.
				socket_close($socket);
				break;
		}

		// Return the latency.
		return $latency;
	}

	/**
	 * Calculate a checksum.
	 *
	 * @param $data (string)
	 *   Data for which checksum will be calculated.
	 *
	 * @return (string)
	 *   Binary string checksum of $data.
	 */
	private function calculateChecksum($data) {
		if (strlen($data) % 2) {
			$data .= "\x00";
		}

		$bit = unpack('n*', $data);
		$sum = array_sum($bit);

		while ($sum >> 16) {
			$sum = ($sum >> 16) + ($sum & 0xffff);
		}

		return pack('n*', ~$sum);
	}

}