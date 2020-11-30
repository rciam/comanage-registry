<?php
/*
 * For execution run:cd /srv/comanage/comanage-registry-current/app && Console/cake upgradeVersionRciam <version>
 * */
class UpgradeVersionRciamShell extends AppShell
{
    var $uses = array('OrgIdentity');

    public function main() {
        $targetVersion = null;
        if(!empty($this->args[0])) {
            // Use requested target version
            $targetVersion = $this->args[0];
            $fn = '_ug' . $targetVersion;
            if(method_exists($this, $fn)) {
                $this->$fn();
            }
            else {
                $this->out(_txt('er.ug.fail'));
                $this->out('This version does not exist.');
                exit;
            }
        }
        else {
            $this->out('Please provide target version');
        }
    }

    public function _ug202011()
    {
        $query = array();
        $query[] = "alter table cm_org_identities add authn_authority varchar(1024);";
        $db = ConnectionManager::getDataSource('default');
        if(isset($db->config['prefix'])) {
            $prefix = $db->config['prefix'];
        }

        $db->begin();
        try {
            foreach ($query as $idx => $qr) {
                $result = $this->OrgIdentity->query($qr);
                $db->commit();
                $this->out('<info>' . ($idx+1) . '. SQL command:</info> ' . $qr);
                $this->out('Query Result: ' . print_r($result, true));
            }
        }
        catch(Exception $e) {
            $db->rollback();
            $this->out('<error>' . $e->getMessage() . '</error>');
        }
    }
}
