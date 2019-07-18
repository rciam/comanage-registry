// Please note that schema generation in 2.x does not handle foreign key constraints.
// -- Add Foreign Key constraints
// ALTER TABLE ONLY public.cm_co_mfa_service_settings ADD CONSTRAINT cm_co_mfa_service_settings_co_id_fkey FOREIGN KEY (co_id) REFERENCES public.cm_cos(id);
// GRANT SELECT ON TABLE public.cm_co_mfa_service_settings TO cmregistryuser_proxy;


// Console/cake schema create --file schema.php --path /srv/comanage/registry-current/local/Plugin/OtpProvisioner/Config/Schema

<?php
class AppSchema extends CakeSchema
{
  
  public function before($event = array())
  {
    return true;
  }
  
  public function after($event = array())
  {
  }
  
  public $co_mfa_service_settings = array(
    'id' => array('type' => 'integer', 'autoIncrement' => true, 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
    'co_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'from' => array('type' => 'string', 'null' => false, 'length' => 48),
    'text' => array('type' => 'string', 'null' => false, 'length' => 512),
    'code_length' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'ttl' => array('type' => 'integer', 'null' => false, 'length' => 24),
    'max_verification_attemps' => array('type' => 'integer', 'null' => false, 'length' => 1),
    'verify_expiration_period' => array('type' => 'integer', 'null' => false, 'length' => 3),
    'utf' => array('type' => 'integer', 'null' => true, 'length' => 10),
    'url' => array('type' => 'string', 'null' => false, 'length' => 512),
    'api_key' => array('type' => 'string', 'null' => false, 'length' => 512),
    'api_secret' => array('type' => 'string', 'null' => false, 'length' => 512),
    'created' => array('type' => 'datetime', 'null' => true),
    'modified' => array('type' => 'datetime', 'null' => true),
    'deleted' => array('type' => 'boolean', 'null' => false, 'default' => false),
    'indexes' => array(
      'PRIMARY' => array('column' => 'id', 'unique' => 1),
      'cm_co_mfa_service_settings_co_id_i1' => array('column' => 'co_id'),
    )
  );
}