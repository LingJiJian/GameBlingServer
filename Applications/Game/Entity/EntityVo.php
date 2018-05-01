<?php 

class EntityVo {

	private $data = array();

    // public $places = array();

	public function __set($name,$value)
	{
		$this->data[$name] = $value;
	}

	public function &__get($name)
	{
		if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
         }
         return null;
	}

	public function __isset($name) 
    {
        return isset($this->data[$name]);
    }

    public function __unset($name) 
    {
        unset($this->data[$name]);
    }

    private function _getData(&$result,$arr)
    {
        foreach ($arr as $key => $value) {
            if(is_array($value)){
                $result[$key] = array();
                $this->_getData($result[$key],$value);
            }elseif(is_object($value)){
                $result[$key] = $value->getData();
            }else{
                $result[$key] = $value;
            }
        }
    }

    public function getData()
    {
        $result = array();
        $this->_getData($result,$this->data);
        return $result;
    }

    public function insertArray(&$arr,$ikey,$ivalue)
    {
        $arr[$ikey] = $ivalue;
        return $arr;
    }
}