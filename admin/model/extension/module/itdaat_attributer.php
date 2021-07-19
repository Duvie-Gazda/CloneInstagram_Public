<?php
require_once (DIR_SYSTEM . '/engine/itdaat_model.php');

class ModelExtensionModuleItdaatAttributer extends ModelItDaat {
    public function __construct(){
        $this->moduleCode = 'attributer';
        $this->run($this->moduleCode);
    }

    public function addItdaat_Attribute($itdaat_attributes_name){
        $this->database->setRequest("insert into oc_itdaat_attribute () values ();");
        $this->database->getAssocRequest("select max(id) as id from oc_itdaat_attribute;");
        $attr_id = ($this->database->getKeyToValue_Field())['id'];
        // $this->log->log($itdaat_attributes_name);
        foreach($itdaat_attributes_name as $language_id =>$attribute_name){
            $this->database->setRequest("insert into oc_itdaat_attribute_name (itdaat_attribute_id, language_id, name) values ({$attr_id},{$language_id},'{$attribute_name}');");
            $this->log->log("insert into oc_itdaat_attribute_name (itdaat_attribute_id, language_id, name) values ({$attr_id},{$language_id},'{$attribute_name}');");
        }
    }


    public function getAttributeToSet(){
        $languages = $this->settings->getValueByKey('languages');
        $language_id = $this->database->getAssocRequest("select language_id from oc_language where name = '{$languages}' ");
        $language_id = (($language_id)[0])['language_id'];
        $attributes_not_formatted = $this->database->getAssocRequest("
            select oc_attribute_description.name, oc_itdaat_dictionary.oc_attribute_value, oc_attribute_description.attribute_id from oc_itdaat_dictionary 
            inner join oc_attribute_description
            on oc_itdaat_dictionary.oc_attribute_id = oc_attribute_description.attribute_id and oc_attribute_description.language_id = oc_itdaat_dictionary.language_id
            where oc_itdaat_dictionary.itdaat_attribute_id is null and oc_itdaat_dictionary.itdaat_attribute_value_id is null and 
            oc_itdaat_dictionary.oc_attribute_id = (select MIN(oc_itdaat_dictionary.oc_attribute_id)  from oc_itdaat_dictionary where oc_itdaat_dictionary.itdaat_attribute_id is null and oc_itdaat_dictionary.itdaat_attribute_value_id is null and oc_itdaat_dictionary.language_id = {$language_id})
            and oc_itdaat_dictionary.language_id = {$language_id}
            ORDER BY oc_itdaat_dictionary.oc_attribute_id ASC
        ");
        $row = [];
        $attributes = [];
        foreach ($attributes_not_formatted as $attribute){
            $attributes['name'] = $attribute['name'];
            $row['value'] = $attribute['oc_attribute_value'];
            $attributes[] = $row;
        }
        return $attributes;
    }
    
    /**
     * connectItdaatAttributes
     * this function connects all attributes in the array
     * @return void
     */
    public function connectItdaatAttributes(){
        $db = new itdaat_database();
        $languages = $this->settings->getValueByKey('languages');
        $language_id = $this->database->getAssocRequest("select language_id from oc_language where name = '{$languages}' ");
        $language_id = (($language_id)[0])['language_id'];
        $query = "
            SELECT
            oc_itdaat_attribute_name.name,
            oc_itdaat_attribute_value.value,
            oc_itdaat_attribute_name.itdaat_attribute_id
            from
            oc_itdaat_attribute
            left join oc_itdaat_attribute_name on oc_itdaat_attribute.id = oc_itdaat_attribute_name.itdaat_attribute_id
            left join oc_itdaat_attribute_value on oc_itdaat_attribute_value.itdaat_attribute_id = oc_itdaat_attribute.id and oc_itdaat_attribute_name.language_id = oc_itdaat_attribute_value.language_id
            where oc_itdaat_attribute_name.language_id = '$language_id' and oc_itdaat_attribute.id = 
        "; 
        $itdaat_attributes = $db->getAssocRequest("SELECT id from oc_itdaat_attribute");
        $res = [];
        $rows = [];
        foreach ($itdaat_attributes as $key => $attr){
            $rows[$key] = $db->getAssocRequest($query . $attr['id'] . " limit 100");
            $r = [];
            foreach ($rows[$key] as &$row){
                $r['name'] = $row['name']; 
                $r['id'] = $row['id'];
                if(isset($r['value'])){
                    $r['value'] = $r['value'] . ', ' . $row['value'];
                } else {
                    $r['value'] = $row['value'];
                }
            }
            if($r != []){
                $res[] = $r;
            }
        }
        return $res;
    }
}