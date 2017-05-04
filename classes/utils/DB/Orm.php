<?php
namespace Presto\Util\DB;

/**
 * Orm::conditions($cond);
 * [a] = [like, 'val', 'null|left|right']
 * [a] = [between,['min', 'max']]
 * [a] = [in, [,,,,,]]
 *
 * 検索条件
 * 
 * #1) a=1 and b=1
 *  [a]=1
 *  [b]=1
 * #2) a=1 or b=1
 *  [or][a]=1
 *  [or][b]=1
 * #3) a=1 and (b=1 or c=1)
 *  [a]=1
 *  [or][b]=1
 *  [or][c]=1
 * #4) (a=1 or b=1) and (c=1 or d=1)
 *  [0][or][a]=1
 *  [0][or][b]=1
 *  [1][or][c]=1
 *  [1][or][d]=1
 * #5) (a=1 and b=1) or (c=1 or d=1)
 *  [0][a]=1
 *  [0][b]=1
 *  [1][or][c]=1
 *  [1][or][d]=1
 *  
 *  
 * @return array 'bind'=>$bind,
 * @return string 'conditions'=>sql where
 */
class Orm
{
    public static function test()
    {
        $cond = [];
        $cond['or'][0]['a'] = ['like', "1%"];
        $cond['or'][1]['a'] = ['in', [1,2,3,4]];
        $cond['or'][2]['a'] = ['between', [1,100]];
        var_dump(self::conditions($cond));

        $cond = [];
        $cond['or'][0]['a'] = ['like', 1];
        $cond['or'][0]['b'] = ['like', 2, 'left'];
        $cond['or'][0]['c'] = ['like', 3, 'right'];
        $cond['or'][1]['a'] = ['between', [1,100]];
        $cond['or'][1]['b'] = ['between', [2,200]];
        $cond['or'][2]['a'] = ['in', [1,2,3,4,5,6,7,8,9,0]];
        $cond['b'] = 2;
        var_dump(self::conditions($cond));
        
        $cond = [];
        $cond['a'] = 1;
        $cond['b'] = 2;
        var_dump(self::conditions($cond));
        
        $cond = [];
        $cond['or']['a'] = 1;
        $cond['or']['b'] = 2;
        var_dump(self::conditions($cond));

        $cond = [];
        $cond['or']['a'] = 1;
        $cond['or']['b'] = 2;
        $cond['c'] = 3;
        var_dump(self::conditions($cond));

        $cond = [];
        $cond['or'][0]['a'] = 1;
        $cond['or'][0]['b'] = 2;
        $cond['or'][1]['c'] = 3;
        $cond['or'][1]['d'] = 4;
        var_dump(self::conditions($cond));

        $cond = [];
        $cond['or']['or']['a'] = 1;
        $cond['or']['or']['b'] = 2;
        $cond['or'][0]['a'] = 11;
        $cond['or'][0]['b'] = 22;
        $cond['or'][1]['c'] = 3;
        $cond['or'][1]['d'] = 4;
        var_dump(self::conditions($cond));

        $cond = [];
        $cond[0]['or']['or']['a'] = 1;
        $cond[0]['or']['or']['b'] = 2;
        $cond[0]['or'][0]['a'] = 11;
        $cond[0]['or'][0]['b'] = 22;
        $cond[1]['or'][1]['c'] = 3;
        $cond[1]['or'][1]['d'] = 4;
        var_dump(self::conditions($cond));

        $cond = [];
        $cond['user_id'] = 1;
        $cond['device_id'] = 'aaaaaaaa';
        $cond[0]['a'] = 1;
        $cond[0]['b'] = 2;
        $cond[0]['or']['c'] = 3;
        $cond[0]['or']['d'] = 4;
        var_dump(self::conditions($cond));
    }

    const CONDITION_CASE = [''];
    const SQL_OPERATORS = ['between', 'like', 'in', '>', '>=', '<', '<=', '<>', '='];
    public static $bind_serial_no = 0; // bindが重複しないように連番

    // bind, where生成
    public static function conditions($condition_params=[], $isOr=false, $clear_bind_serial_no=true)
    {
        if(empty($condition_params)) { return [null, null]; }

        self::$bind_serial_no = $clear_bind_serial_no ? 0 : self::$bind_serial_no;
        $sql_where = "";
        $bind = [];

        foreach ($condition_params as $key=>$param){

            if( ! is_array($param) ){
                $bind_field_name = "{$key}_" . ++self::$bind_serial_no;
                $bind[$bind_field_name] = $param;

                if($isOr){
                    $sql_where .= " {$key}=:{$bind_field_name}: ";
                }else{
                    $sql_where .= " AND {$key}=:{$bind_field_name}: ";
                }
            }elseif(!empty($param[0]) && in_array($param[0], self::SQL_OPERATORS)){
                // like, between, in
                switch ($param[0]){
                    case 'between':
                        // $param => [between, [min, max]]
                        list($sql_where_sub, $bind_sub) = self::__array_to_sql_between($key, $param);
                        break;
                    case 'like':
                        // $param => [like, val, left|right]
                        list($sql_where_sub, $bind_sub) = self::__array_to_sql_like($key, $param);
                        break;
                    case 'in':
                        // $param => [in, [,,,,,]]
                        list($sql_where_sub, $bind_sub) = self::__array_to_sql_in($key, $param);
                        break;
                    default:
                        throw new \Presto\Exception\ApplicationException("File:" .__FILE__ . ", Line:" .__LINE__);
                        break;
                }

                $bind = array_merge($bind, $bind_sub);

                if($isOr){
                    $sql_where .= " {$sql_where_sub} ";
                }else{
                    $sql_where .= " AND {$sql_where_sub} ";
                }
            }elseif(strtolower($key) == 'or'){
                // or group
                $sql_where_tmp = "";

                foreach ($param as $key_1=>$val_1){
                    list($result['conditions'], $result['bind']) = self::conditions([$key_1=>$val_1], true, false);
                    $sql_where_tmp .= " OR {$result['conditions']} ";
                    $bind = array_merge($bind, $result['bind']);
                }

                $sql_where_tmp = self::sanitize($sql_where_tmp);
                $sql_where .= " AND ({$sql_where_tmp}) ";

            }elseif(is_numeric($key)){
                // group
                $sql_where_tmp = "";

                foreach ($param as $key_1=>$val_1){
                    list($result['conditions'], $result['bind']) = self::conditions([$key_1=>$val_1], false, false);
                    $sql_where_tmp .= " AND {$result['conditions']} ";
                    $bind = array_merge($bind, $result['bind']);
                }

                $sql_where_tmp = self::sanitize($sql_where_tmp);
                $sql_where .= " AND ({$sql_where_tmp}) ";
            }else{
                throw new \Presto\Exception\ApplicationException("File:" .__FILE__ . ", Line:" .__LINE__);
            }
        }

        $sql_where = self::sanitize($sql_where);
        return [$sql_where, $bind];
    }


    /**
     * [a][between] = [min, max]
     * @param unknown $key
     * @param unknown $val
     * @return string[]|unknown[]
     */
    public static function __array_to_sql_between($key, $val)
    {
        $bind_field_name_min = $key .'_'. ++self::$bind_serial_no;
        $bind_field_name_max = $key .'_'. ++self::$bind_serial_no;

        $sql_where = " {$key} BETWEEN :{$bind_field_name_min}: AND :{$bind_field_name_max}: ";
        $bind[$bind_field_name_min] = $val[1][0];
        $bind[$bind_field_name_max] = $val[1][1];

        return [$sql_where, $bind];
    }


    /**
     * [a][like]=>[val, null|left|right]
     * @param unknown $field
     * @param unknown $cond [a][like]=>[val, position]
     */
    public static function __array_to_sql_like($key, $val)
    {
        $bind_field_name = $key .'_'. ++self::$bind_serial_no;
        $bind[$bind_field_name] = $val[1];
        $sql_where = " {$key} LIKE :{$bind_field_name}: ";
    
        return [$sql_where, $bind];
    }


    /**
     * @param unknown $key field_name
     * @param unknown $val []
     * @return string[]|unknown[]
     */
    public static function __array_to_sql_in($key, $val)
    {
        $bind_field_name = $key .'_'. ++self::$bind_serial_no;
        $bind[$bind_field_name] = $val;
        $sql_where = " {$key} IN (:{$bind_field_name}:) ";

        return [$sql_where, $bind];
    }


    // WHERE文の整理
    const SANITIZE_PREG = [
            ['in'=>"/^\s{0,}AND /",     'out'=>' '],    // 先頭のAND
            ['in'=>"/^\s{0,}OR /",      'out'=>' '],    // 先頭の(OR
            ['in'=>"/\(\s{0,}AND /",    'out'=>' ( '],  // 先頭の(AND
            ['in'=>"/\(\s{0,}OR /",     'out'=>' ( '],  // 先頭のOR
            ['in'=>"/\s{2,}/",          'out'=>' '],    // 2重空白
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
