server {
    listen      %ip%:%proxy_port%;
    server_name %domain% %alias_idn%;
    access_log  /var/log/nginx/app-proxy-%domain%.log main;
    location / {
      proxy_pass  http://%target_ip%:%target_port%;
      proxy_http_version 1.1;
      proxy_set_header Upgrade $http_upgrade;
      proxy_set_header Connection "upgrade";
      proxy_set_header Host $http_host;
      proxy_set_header X-Forwarded-Proto $scheme;
      proxy_set_header X-Forwarded-For $remote_addr;
      proxy_set_header X-Forwarded-Port $server_port;
      proxy_set_header X-Request-Start $msec;
   }
}