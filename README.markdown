##VMCPHP<small> - VMC Library in PHP</small>


VMCPHP is built to replicate the VMC Library as closely as possible.

###Example <small>- Printing HTML List of Apps

Here is an example implementation of VMCPHP that connects to a [VCAP](https://github.com/CloudFoundry/vcap) [(CloudFoundry)](http://cloudfoundry.com) Server with credentials and lists all the apps running for that user.

```php
<?php
require_once('VMCPHP.php');

$VMC = new VMCPHP;
$VMC->target = 'http://api.mycloudfoundryaddress.co.uk';
$VMC->login('my@email.co.uk', 'mypassword');
?>

<ul>
	<?php foreach($VMC->apps() as $app) {
		echo '<li>' . $app['name'] . '</li>';
	} ?>
</ul>
```

When using the correct credentials and address for your [VCAP](https://github.com/CloudFoundry/vcap) [(CloudFoundry)](http://cloudfoundry.com) Server will give you something like…
	<ul>
		<li>dashboard</li>
		<li>apimashup</li>
		<li>wordpressexample</li>
		<li>thebuggenie</li>
	</ul>
###Need More Help?
At the moment there is no documentation as such for the class. There will be some to follow at some point depending on demand. For now if you need additional help other than browsing through the code then Message me!

###Wanting To Help?
This Project has been open-sourced primarily for the use of others without having to work someone else has done. If you find a bug, by all means report it but if you can fix it, your doing myself and others a favour.

Once you've written the code just submit it as a pull request on the <b>Develop Branch</b> and I'll try to review as soon as I can.