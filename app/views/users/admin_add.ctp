<?php
	echo $form->create();
	echo $this->element('users/add_form_core');
	echo $form->submit(__('Add User', true), array( 'class'=> 'btn-primary'));
	echo $form->end();
?>