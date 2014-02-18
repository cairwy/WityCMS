<?php defined('WITYCMS_VERSION') or die('Access denied'); ?>
<?xml version="1.0" encoding="utf-8" ?>
<app>
	<!-- Application name -->
	<name>Configuration</name>
	
	<version>0.1</version>
	
	<!-- Last update date -->
	<date>10-01-2014</date>
	
	<!-- Permissions -->
	<permission name="config_cms" />
	<permission name="config_app" />
	
	<!-- Front actions -->
	
	<!-- Admin actions -->
	<admin>
		<action default="default" description="WityCMS Config">witycms</action>
		<action default="default" description="Apps Config">apps</action>
	</admin>
</app>
