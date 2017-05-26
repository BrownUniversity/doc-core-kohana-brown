<h1>User Impersonation Search</h1>
<p><?php echo \HTML::anchor('impersonate', 'New Search') ?></p>
<h2>Search Results</h2>
<table>
    <tbody>
    <?php foreach ($results as $k => $v) : ?>
        <tr>
            <td>
                <?php echo \HTML::anchor("impersonate/assume/{$k}", "{$v['full_name']} ({$v['primary_affiliation']})") ?>
                </a><br /><i><?php echo $v['title'] ?></i><br />
                <?php echo $v['department'] ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
