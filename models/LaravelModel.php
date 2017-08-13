<?php
namespace App\Models;

class Model extends \Illuminate\Database\Eloquent\Model
{
    const LIMIT_MAX = 1000;
    const SQL_OPERATORS = ['between', 'like', 'in', 'not in', '>', '>=', '<', '<=', '<>', '=', 'is null', 'is not null'];
    
    /**
     * find関数のオーバーライド
     * @param array $parameters
     *      $parameters['order'] = ['user_id'=>'DESC', 'modified'=>'ASC'];
     *      $parameters['limit'] = 1~n;
     *      $parameters['offset'] = 0~n;
     *      
     *      $parameters['conditions']
     *          CASE#1-1 ) WHERE A.word_id=1 AND B.shard_id=1
     *          $parameters['conditions'] = ['word_id'=>1, 'shard_id'=>1];
     *          
     *          CASE#1-2 ) WHERE A.word_id=1 AND B.shard_id IN (1,2,3)
     *          $parameters['conditions'] = ['word_id'=>1, 'shard_id'=>[1,2,3]];
     *          
     *          CASE#1-3 ) WHERE A.word_id<>1 AND B.shard_id BETWEEN 1 AND 99
     *          $parameters['conditions'] = ['word_id'=>['<>' ,1], 'shard_id'=>['between', 1, 999]];
     *          
     *          CASE#1-4 ) TODO WHERE A.name LIKE '%xxx%'
     *          $parameters['conditions'] = ['name'=>['LIKE' ,1 , '|inner|left|right']];
     *          
     *          CASE#2 ) OR
     *          CASE#3 ) GROUP, DISTINCT
     *          
     *          
     *      ['user_id'] = 1;
     *      $parameters['conditions']['user_id'] = 1;
     *      $parameters['conditions']['user_id'] = 1;
     *      
     */
    public static function find($parameters=[])
    {
        $query = (new static)->newQuery();

        // where文の作成
        if(! empty($parameters['conditions']))
        {
            $query = self::__where($query, $parameters['conditions']);
        }

        // offset limit
        $offset = empty($parameters['offset']) ? 0 : $parameters['offset'];
        $limit = empty($parameters['limit']) ? self::LIMIT_MAX : $parameters['limit'];
        $query->skip($offset);
        $query->take($limit);

        // order by
        if(! empty($parameters['order']))
        {
            foreach ($parameters['order'] as $key=>$val)
            {
                $query->orderBy($key, $val);
            }
        }

        return $query->get();
    }


    /**
     * all関数のオーバーライド
     */
    public static function all($parameters=[])
    {
        return self::find($parameters);
    }


    // First
    public static function findFirst($parameters=[])
    {
        $parameters['conditions']['limit'] = 1;
        $parameters['conditions']['offset'] = 0;
        $result =  self::find($parameters);

        return empty($result[0]) ? null : $result[0];
    }


    // TODO order by を明視的に指定しないといけない
    public static function findLast($parameters=[])
    {
        $parameters['conditions']['limit'] = 1;
        $parameters['conditions']['offset'] = 0;
        $result =  self::find($parameters);

        return empty($result[0]) ? null : $result[0];
    }


    public static function findByPk($parameters=[])
    {
        $parameters['conditions']['limit'] = 1;
        $parameters['conditions']['offset'] = 0;
        $result =  self::find($parameters);

        return empty($result[0]) ? null : $result[0];
    }


    public static function count($parameters=[])
    {
        $query = (new static)->newQuery();

        // where文の作成
        if(! empty($parameters['conditions']))
        {
            $query = self::__where($query, $parameters['conditions']);
        }

        return $query->count();
    }


    public static function paging($parameters=[])
    {
        $datas = self::find($parameters);
        $count = self::count($parameters['conditions']);

        return ['datas'=>$datas, 'count'=>$count];
    }


    /**
     * 
     * @param unknown $query
     * @param array $condtions
     * @return unknown
     * 
     * OR CASE#1
     *      WHERE A=1 OR B=2
     *      ['conditions'][or][A] = 1
     *      ['conditions'][or][B] = 2
     * OR CASE#2
     *      WHERE A=1 OR (B=2 AND C=3)
     *      ['conditions'][or][A] = 1
     *      ['conditions'][or][0][B] = 2
     *      ['conditions'][or][0][C] = 3
     * OR CASE#3
     *      WHERE A=1 AND (B=2 OR C=3)
     *      ['conditions'][A] = 1
     *      ['conditions'][0][or][B] = 2
     *      ['conditions'][0][or][C] = 3
     * IN CASE#1
     *      ['conditions'][A] = [1,2,3]
     *      WHERE A IN (1,2,3)
     */
    private static function __where($query, $condtions=[])
    {
        if(empty($condtions)) 
        {
            return $query;
        }

        foreach ($condtions as $key=>$val)
        {
            if(strtolower($key) == 'or')
            {
                // or条件
                $query = self::__where_or($query, $val);
            }
            elseif(is_numeric($key))
            {
                // TODO group化した条件
                $query->where(function($query){
                    self::__where($query, $val);
                });
            }
            elseif(!empty($val[0]) && in_array($val[0], self::SQL_OPERATORS))
            {
                // IN, LIKE, BETWEEN, =, >, >=, <, <=, <>, is null, is not null
                $query = self::__where_sql_operators($query, $key, $val);
            }
            else 
            {
                $query->where($key, $val);
            }
        }

        return $query;
    }


    private static function __where_or($query, $condtions=[])
    {
        foreach ($condtions as $key=>$val)
        {
            // TODO
            $query->orWhere( function() use($query, $key, $val){
                return self::__where_sql_operators($query, $key, $val);
            });
        }

        return $query;
    }


    /**
     * TODO $or_flagをなくす
     */
    protected static function __where_sql_operators($query, $key, $val=[])
    {
        switch (strtolower($val[0]))
        {
            case 'in':
                $query->whereIn($key, $val[1]);
                break;
            case 'not in':
                $query->whereNotIn($key, $val[1]);
                break;
            case 'like':
                if(empty($val[2])){
                    $query->where($key, 'LIKE', "%{$val[1]}%"); // inner like
                }
                elseif('left' == strtolower($val[2]))
                {
                    $query->where($key, 'LIKE', "%{$val[1]}"); // left like
                }
                elseif('right' == strtolower($val[2]))
                {
                    $query->where($key, 'LIKE', "{$val[1]}%"); // right like
                }
                else 
                {
                    $query->where($key, 'LIKE', "%{$val[1]}%"); // inner like
                }

                break;
            case 'between':
                $query->whereBetween($key, $val[1]);
                break;
            default:
                // >, >=, <, <=, <>, =
                $query->where($key, $val[0], $val[1]);
                break;
        }

        return $query;
    }
}
