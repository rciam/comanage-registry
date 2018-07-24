<?php
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_voms_provisioner_texts['en_US'] = array(
	// Titles, per-controller
	'ct.co_voms_provisioner_targets.1'   => 'VOMs Provisioner Target',
	'ct.co_voms_provisioner_targets.pl'  => 'VOMs Provisioner Targets',

	// Error messages
	'er.vomsprovisioner.connect'            => 'Failed to connect to VOMs web services server',
	'er.vomsprovisioner.subject'            => 'Could not determine co person subject dn',
	'er.vomsprovisioner.issuer'             => 'Could not determine certificate issuer',
	'er.vomsprovisioner.canonical'          => 'Could not determine co person canonical name',

	'pl.vomsprovisioner.serverurl'          => 'Base server URL',
	'pl.vomsprovisioner.serverurl.desc'     => 'URL for host (<font style="font-family:monospace">https://hostname[:port]</font>)',
	'pl.vomsprovisioner.voname'             => 'Vo name',
	'pl.vomsprovisioner.voname.desc'        => 'Name of the virtual organization to be used alongside with the host name for the url construction',
	'pl.vomsprovisioner.entity_type'        => 'Entity Type',
	'pl.vomsprovisioner.entity_type.desc'   => 'Entity Type that will trigger the provisioning for VOMs subscription or removal',
	// Plugin texts
	'pl.vomsprovisioner.info'               => 'The VOMs web services server must be available and the co person should be enrolled through RCAUTH plugin before save.'

	// Success messages

);
