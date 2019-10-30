# feedly-saved-hook

```sh
# Docker ホストを環境変数で指定
export DOCKER_HOST=ssh://$MY_REMOTE_SERVER

# コンテナを開始
docker-compose build
docker-compose up -d

# コンテナの IP アドレスを確認
docker inspect feedly-saved-hook | jq '.[].NetworkSettings.Networks[].IPAddress' -r

# ホストにログインしつつコンテナで Web サーバを実行しつつポートフォワード
ssh -L 8080:172.21.0.2:8080 -N "$MY_REMOTE_SERVER"

# ブラウザで http://localhost:8080/ を開くと Feedly の OAuth 認証になるので認証を済ます
open http://localhost:8080/

# 認証が終わったら↑のポートフォワードは停止する

# 動作確認
ssh "$MY_REMOTE_SERVER" docker exec feedly-saved-hook php script/main.php

# cron にバッチを仕込む
echo "*/10 * * * * root chronic mispipe 'docker exec feedly-saved-hook php script/main.php' 'logger -st feedly'" |
  ssh "$MY_REMOTE_SERVER" sudo tee /etc/cron.d/feedly-saved-hook
ssh "$MY_REMOTE_SERVER" sudo systemctl reload crond
```

## link

- https://gist.github.com/d3m3vilurr/5904029
