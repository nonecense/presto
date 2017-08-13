<?php
namespace App\Quest;

class BattleLogic
{
    public $action_logs = [];
    public $deck_user;
    public $deck_enemy;

    /**
     * 行動順番作成
     * @param array $user_decks
     * @param array $enemy_decks
     */
    public function actions($user_decks=[], $enemy_decks=[])
    {
        // ----------------------------------------------
        // speedより行動順を作成する
        // ----------------------------------------------
        $total_hp_user_deck = 0;
        $total_hp_enemy_deck = 0;

        // ユーザスピード計算
        foreach ($user_decks as $key=>$val)
        {
            $user_decks[$key]['speed_value'] = self::get_speed_value($val);
            $user_decks[$key]['is_enemy'] = 0;
            $user_deck_total_hp += $val['hp_now'];
        }

        // 敵のスピード計算
        foreach ($enemy_decks as $key=>$val)
        {
            $enemy_decks[$key]['speed_value'] = self::get_speed_value($val);
            $user_decks[$key]['is_enemy'] = 1;
            $enemy_deck_total_hp += $val['hp_now'];
        }

        // userまたはenemy一方のHP全部0の場合、生き残った方の勝利にする
        if($total_hp_user_deck <= 0 || $total_hp_enemy_deck <= 0)
        {
            // TODO 一方が勝利で終了処理
        }

        // 配列をマージする
        $this->deckes = array_merge($user_decks, $enemy_decks);

        // スピードが早い順でソートする
        \App\Utility\ArrayData::sort($this->deckes, 'speed_value', \App\Utility\ArrayData::SORT_DESC);
        // ----------------------------------------------


        // ----------------------------------------------
        // 行動を演算する
        // ----------------------------------------------
        foreach ($this->deckes as $key=>$val)
        {
            // 行動or発動スキルを決める
            

            // 行動orスキル発動対象を決める
            

            // スキル結果を演算する
            
        }
        // ----------------------------------------------

        return [$this->deckes, $this->action_logs];
    }


    public static function get_speed_value($characater_data)
    {
        return $characater_data['speed'] * ($characater_data['hp_now'] / $characater_data['hp']);
    }
    
    
    public function calc_skill($actor, $target=[], $skill)
    {
        switch ($skill['type'])
        {
            
        }
    }

}
