# feedly-saved-hook

```sh
ssh -L 8080:127.0.0.1:1234 remote-server

git clone git@github.com:ngyuki/feedly-saved-hook.git
cd feedly-saved-hook

sudo /usr/local/bin/docker-compose run --rm -p 1234:1234 app php -S 0.0.0.0:1234 -t script/
```

Open http://localhost:8080/ in browser.

```sh
sudo /usr/local/bin/docker-compose run --rm app
```

```sh
cat <<EOS | sudo tee /etc/cron.d/feedly-saved-hook
PATH=/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin
*/10 * * * * root cd "$PWD" && docker-compose run --rm app | logger -t feedly-saved-hook
EOS

sudo systemctl reload crond
```

## link

- https://gist.github.com/d3m3vilurr/5904029
