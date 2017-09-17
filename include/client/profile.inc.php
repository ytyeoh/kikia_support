<h1><?php echo __('Manage Your Profile Information'); ?></h1>
<p><?php echo __(
'Use the forms below to update the information we have on file for your account'
); ?>
</p>
<form action="profile.php" method="post">
  <?php csrf_token(); ?>
<table class="padded">
<?php
	$checkldap = 'SELECT * FROM '.PLUGIN_TABLE
                .' WHERE name LIKE "%ldap%" AND `isactive` = "1"';
	$openTickets = $thisclient->getNumOpenTickets($org_tickets);
    $closedTickets = $thisclient->getNumClosedTickets($org_tickets);
foreach ($user->getForms() as $f) {
    $f->render(false);
}
if ($acct = $thisclient->getAccount()) {
    $info=$acct->getInfo();
    $info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<div class="container">
	<div class="row">
		<div class="col-md-offset-2 col-md-8 col-lg-offset-3 col-lg-6">
    	 <div class="well profile">
            <div class="col-sm-12">
                <div class="col-xs-12 col-sm-8">
                    <h2><?php echo Format::htmlchars($thisclient->getName());?></h2>
                    <p><strong>email: </strong> <?php echo Format::htmlchars($thisclient->getEmail());?> </p>
                    <p><strong>Hobbies: </strong> Read, out with friends, listen to music, draw and learn new things. </p>
                    <p><strong>Skills: </strong>
                        <span class="tags">html5</span> 
                        <span class="tags">css3</span>
                        <span class="tags">jquery</span>
                    </p>
                </div>             
            </div>            
            <div class="col-xs-12 divider text-center">
                <div class="col-xs-12 col-sm-4 emphasis">
                    <h2><strong> <?php echo $openTickets ?> </strong></h2>                    
                    <p><small>Open Tickets</small></p>
                    <button class="btn btn-success btn-block"><span class="fa fa-plus-circle"></span> Follow </button>
                </div>
                <div class="col-xs-12 col-sm-4 emphasis">
                    <h2><strong><?php echo $closedTickets ?></strong></h2>                    
                    <p><small>Closed Tickets</small></p>
                    <button class="btn btn-info btn-block"><span class="fa fa-user"></span> View Profile </button>
                </div>
            </div>
    	 </div>                 
		</div>
	</div>
</div>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Preferences'); ?></h3>
        </div>
    </td>
</tr>
    <tr>
        <td class="text-nowrap">
            <?php echo __('Time Zone');?>:
        </td>
        <td>
            <?php
            $TZ_NAME = 'timezone';
            $TZ_TIMEZONE = $info['timezone'];
            include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
            <div class="error"><?php echo $errors['timezone']; ?></div>
        </td>
    </tr>
<?php if ($cfg->getSecondaryLanguages()) { ?>
    <tr>
        <td class="text-nowrap">
            <?php echo __('Preferred Language'); ?>:
        </td>
        <td>
    <?php
    $langs = Internationalization::getConfiguredSystemLanguages(); ?>
            <select name="lang">
                <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
<?php foreach($langs as $l) {
$selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                    ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
            </select>
            <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
        </td>
    </tr>
<?php }
      if ($acct->isPasswdResetEnabled()) { ?>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Access Credentials'); ?></h3></div>
    </td>
</tr>
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('Current Password'); ?>:
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
    </td>
</tr>
<?php } ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('New Password'); ?>:
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </td>
</tr>
<tr>
    <td class="text-nowrap">
        <?php echo __('Confirm New Password'); ?>:&nbsp;
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </td>
</tr>
<?php } ?>
<?php } ?>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" class="btn btn-success" value="Update"/>
    <input type="reset" class="btn btn-warning" value="Reset"/>
    <input type="button" class="btn btn-default" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
