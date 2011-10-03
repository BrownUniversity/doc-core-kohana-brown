<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>User Impersonation</title>

</head>

<body>
<style type="text/css">
body {
	background : #000000;
	color : #7CFC00;
	text-align: center;
}

a {
	color : #7CFC00;
	text-decoration : italic;
}

#content {
	display : inline-block;
	border : 2px solid #7CFC00;
	padding : 10px;
	margin-top: 20px;
	border-radius : 10px;
	-moz-border-radius : 10px;
	-webkit-border-radius : 10px;
}
#alert {
	text-align: center ;
}
#alert div {
	color: red;
	font-weight: bold;
	border: 2px solid red;
	padding: 10px;
	margin: 20px auto 10px auto;
	border-radius: 10px;
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	width: 240px;
}
table {
	width : 100%;
}
td {
	text-align : center;
	padding : 5px 0px;
	border-bottom : 1px dashed #7CFC00;
}
</style>
<div id='content'>
<?php if (isset($content)) echo $content ?>
</div>
</body>
</html>