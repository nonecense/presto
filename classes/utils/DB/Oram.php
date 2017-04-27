<?php
namespace Presto\Util\DB;

class Orm
{
    public static function test()
    {
        $cond = [];
        $cond['user_id'] = 1;
        $cond['device_id'] = 'aaaaaaaa';
        $cond[0]['a'] = 1;
        $cond[0]['b'] = 2;
        $cond[0]['or']['c'] = 3;
        $cond[0]['or']['d'] = 4;
        var_dump(self::conditions($cond));
        
        $cond = [];
        $cond['or'][0]['a'] = 1;
        $cond['or'][0]['b'] = 2;
        $cond['or'][1]['c'] = 3;
        $cond['or'][1]['d'] = 4;
        
        var_dump(self::conditions($cond));
    }


    const ORM_KEY = ['or'];
    const SQL_OPERATORS = ['between', 'like', 'in', '>', '>=', '<', '<=', '<>', '='];

    public static $bind_serial_no = 0; // bindが重複しないように連番

    // bind, where生成
    public static function conditions($params=[], $isOr=false, $clear_bind_serial_no=true)
    {
        $sql_where = "";
        $bind = [];

        foreach ($params as $key=>$param){
            if(!is_array($param)){
                $bind_field_name = "{$key}_" . ++self::$bind_serial_no;
                $bind[$bind_field_name] = $param;
                if($isOr){
                    $sql_where .= " {$key}=:'{$bind_field_name}': ";
                }else{
                    $sql_where .= " AND {$key}=:'{$bind_field_name}': ";
                }
            }elseif(!empty($param[0]) && in_array($param[0], self::SQL_OPERATORS)){
                
            }elseif(strtolower($key) == 'or'){
                // or group
                $sql_where_tmp = "";
                foreach ($param as $key_1=>$val_1){
                    $result = self::conditions([$key_1=>$val_1], true, false);
                    $sql_where_tmp .= " OR {$result['conditions']} ";
                    $bind = array_merge($bind, $result['bind']);
                }
                $sql_where_tmp = self::sanitize($sql_where_tmp);
                $sql_where .= " ({$sql_where_tmp}) ";
            }elseif(is_numeric($key)){
                // group
                $sql_where_tmp = "";
                foreach ($param as $key_1=>$val_1){
                    $result = self::conditions([$key_1=>$val_1], false, false);
                    $sql_where_tmp .= " AND {$result['conditions']} ";
                    $bind = array_merge($bind, $result['bind']);
                }
                $sql_where_tmp = self::sanitize($sql_where_tmp);
                $sql_where .= " AND ({$sql_where_tmp}) ";
            }else{
                throw new \Presto\Exception\ApplicationException("\Presto\Util\DB sql conditions error!");
            }
        }

        if($clear_bind_serial_no){
            self::$bind_serial_no = 0;
        }

        $sql_where = self::sanitize($sql_where);
        return ['conditions'=>$sql_where, 'bind'=>$bind];
    }


    /**
     * [a][like]=>[val, null|left|right]
     * @param unknown $field
     * @param unknown $cond [a][like]=>[val, position]
     */
    public static function array_to_like($field, $val)
    {
        $sql_where = "";
        $bind_field_name = $key .'_'. ++self::$bind_serial_no;
        if(empty($val[1])){
            $sql_where = "AND {$key} {$val[0]} LIKE '%:{$bind_field_name}:%'";
        }elseif(strtolower($val[1]) == 'left'){
            $sql_where = "AND {$key} {$val[0]} LIKE '%:{$bind_field_name}:'";
        }elseif(strtolower($val[1]) == 'right'){
            $sql_where = "AND {$key} {$val[0]} LIKE ':{$bind_field_name}:%'";
        }else{
            throw new \Presto\Exception\ApplicationException("presto orm like conver error!");
        }

        $bind[$bind_field_name] = $val[0];
        return ['conditions'=>$sql_where, 'bind'=>$bind];
    }



    /**
     * [a][between] = [min, max]
     * @param unknown $key
     * @param unknown $val
     * @return string[]|unknown[]
     */
    public static function array_to_between($key, $val)
    {
        $bind_field_name_min = $key .'_'. ++self::$bind_serial_no;
        $bind_field_name_max = $key .'_'. ++self::$bind_serial_no;

        $sql_where = " {$key} BETWEEN ':{$bind_field_name_min}:' AND ':{$bind_field_name_max}:' ";
        $bind[$bind_field_name_min] = $val[0];
        $bind[$bind_field_name_max] = $val[1];

        return ['conditions'=>$sql_where, 'bind'=>$bind];
    }


    // WHERE文の整理
    const SANITIZE_PREG = [
            ['in'=>"/^\s{1,}/",         'out'=>' '], // 先頭の空白
            ['in'=>"/^AND /",           'out'=>' '], // 先頭のAND
            ['in'=>"/^OR /",            'out'=>' '], // 先頭のOR
            ['in'=>"/\(\s{0,}AND /",    'out'=>' '], // 先頭のAND
            ['in'=>"/\(\s{0,}OR /",     'out'=>' '], // 先頭のOR
            ['in'=>"/\s{2,}/",          'out'=>' '], // 2重空白
    ];


    // WHERE文の整理
    public static function sanitize($sql_where="")
    {
        $sql_where = trim($sql_where, ' ');

        foreach (self::SANITIZE_PREG as $key=>$val){
            $sql_where = preg_replace($val['in'], $val['out'], $sql_where);  // 2重空白
        }

        return trim($sql_where, ' ');
    }
}
