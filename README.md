# Presto
PHP Backend System
<img src="http://i.imgur.com/vhQfmAc.png" alt="" title=""><br>
<img src="http://i.imgur.com/wOGk40G.png" alt="" title="">


### やりたいこと
#### 軽量、負荷対策

#### DB分割とシャーディング
- master
- slave
- shard[0-9]
- log[0-9]

#### API
- Utility(Logger, Profiling、集計用ログ作成、エラーレポート)
- 認証、課金(iOS, Android, Gree, Mixi, NicoApp ...)
- Model改良
<pre>
メモリキャッシュ(同じデータを複数回findしてもDBへのリクエストは１回のみ送るようにする)
無駄な更新を無視(DIRTY_STATE)
Sharding、Master Slave
Redisへのキャッシュ
</pre>
- 通信障害時のRetry仕組み


#### 管理ツール
- KPI(Timely、Daily、Weekly、Monthly、Etc)
- 会員管理(検索、詳細参照、各種所持や履歴情報参照)
- マスター管理
- バッチ管理
- プラットフォーム設定
- デバッグ機能(Timecop、パラメータ偽装等)

#### サーバー構成
- CDN
- API
- DB
- Redis
















#### Phalcon\Mvc\Model
<pre>
Constants
integer OP_NONE
integer OP_CREATE
integer OP_UPDATE
integer OP_DELETE
integer DIRTY_STATE_PERSISTENT
integer DIRTY_STATE_TRANSIENT
integer DIRTY_STATE_DETACHED
</pre>
