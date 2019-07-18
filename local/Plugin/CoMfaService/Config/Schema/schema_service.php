<?php

// -- Add Foreign Key constraints
// ALTER TABLE ONLY public.cm_co_mfa_services ADD CONSTRAINT cm_co_mfa_services_co_id_fkey FOREIGN KEY (co_id) REFERENCES public.cm_cos(id);
// ALTER TABLE ONLY public.cm_co_mfa_services ADD CONSTRAINT cm_co_mfa_services_co_person_id_fkey FOREIGN KEY (co_person_id) REFERENCES public.cm_co_people(id);
// ALTER TABLE ONLY public.cm_co_mfa_services ADD CONSTRAINT cm_co_mfa_services_telephone_number_id_fkey FOREIGN KEY (telephone_number_id) REFERENCES public.cm_telephone_numbers(id);
// ALTER TABLE ONLY public.cm_co_mfa_services ADD CONSTRAINT cm_co_mfa_services_co_mfa_service_setting_id_fkey FOREIGN KEY (co_mfa_service_setting_id) REFERENCES public.cm_co_mfa_service_settings(id);
// GRANT SELECT ON TABLE public.cm_co_mfa_services TO cmregistryuser_proxy;


// Console/cake schema create --file schema.php --path /srv/comanage/registry-current/local/Plugin/OtpProvisioner/Config/Schema


class AppSchema extends CakeSchema
{
  
  public function before($event = array())
  {
    return true;
  }
  
  public function after($event = array())
  {
  }
  
  public $co_mfa_services = array(
    'id' => array('type' => 'integer', 'autoIncrement' => true, 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
    'co_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'co_person_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'telephone_number_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'co_mfa_service_setting_id' => array('type' => 'integer', 'null' => false, 'length' => 10),
    'verification_count' => array('type' => 'integer', 'null' => true),
    'verified' => array('type' => 'boolean', 'null' => false, 'default' => 'false'),
    'created' => array('type' => 'datetime', 'null' => true),
    'modified' => array('type' => 'datetime', 'null' => true),
    'deleted' => array('type' => 'boolean', 'null' => false, 'default' => false),
    'indexes' => array(
      'PRIMARY' => array('column' => 'id', 'unique' => 1),
      'cm_co_mfa_services_i1' => array('column' => 'co_id'),
      'cm_co_mfa_services_i2' => array('column' => 'co_person_id'),
      'cm_co_mfa_services_i3' => array('column' => 'telephone_number_id', 'unique' => 1),
      'cm_co_mfa_services_i4' => array('column' => 'co_mfa_service_setting_id'),
    )
  );
}
