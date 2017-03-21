# presto
PHP backend game system

### やりたいこと
#### DB分割とシャーディング
- master
- slave
- shard[0-9]
- log[0-9]

#### 管理ツール
- KPI(Timely、Daily、Weekly、Monthly、Etc)
- 会員管理(検索、詳細参照、各種所持や履歴情報参照)
- マスター管理
- バッチ管理
- デバッグ機能(Timecop、パラメータ偽装等)




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
