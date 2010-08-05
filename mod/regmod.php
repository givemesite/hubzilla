<?php



function regmod_content(&$a) {

	if(! local_user()) {
		notice( t('Please login.') . EOL);
		$o .= '<br /><br />' . login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
		return $o;
	}

	if($a->argc != 3)
		killme();

	$cmd = $a->argv[1];
	$hash = $a->argv[2];


	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);


	if(! count($register))
		killme();

	if($cmd == 'deny') {

		$r = q("DELETE FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($register[0]['uid'])
		);
		$r = q("DELETE FROM `contact` WHERE `uid` = %d",
			intval($register[0]['uid'])
		); 
		$r = q("DELETE FROM `profile` WHERE `uid` = %d",
			intval($register[0]['uid'])
		); 

		$r = q("DELETE FROM `register` WHERE `hash` = '%s' LIMIT 1",
			dbesc($register[0]['hash'])
		);
		notice( t('Registration revoked.') . EOL);
		return;

	}

	if($cmd == 'allow') {

		$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($register[0]['uid'])
		);
		if(! count($user))
			killme();

		$r = q("DELETE FROM `register` WHERE `hash` = '%s' LIMIT 1",
			dbesc($register[0]['hash'])
		);


		$r = q("UPDATE `user` SET `blocked` = 0, `verified` = 1 WHERE `uid` = %d LIMIT 1",
			intval($register[0]['uid'])
		);
		
		$email_tpl = file_get_contents("view/register_open_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $user[0]['username'],
				'$email' => $user[0]['email'],
				'$password' => $register[0]['password'],
				'$uid' => $user[0]['uid']
		));

		$res = mail($user[0]['email'], t('Registration details for '). $a->config['sitename'],
			$email_tpl,'From: ' . t('Administrator@') . $_SERVER[SERVER_NAME] );


		if($res) {
			notice( t('Account approved.') . EOL );
			return;
		}

	}
}