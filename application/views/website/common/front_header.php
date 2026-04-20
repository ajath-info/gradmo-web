<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?php echo isset($html_lang) ? html_escape($html_lang) : 'en'; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo isset($title) ? html_escape($title) : 'Gradmo'; ?></title>
	<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>" />
</head>
<body>
	<div id="frontend-wrapper">
