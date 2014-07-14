server {
  listen      %ip%:%proxy_port%;
  listen      %ip%:%proxy_ssl_port%;
  server_name %alias_idn%;
  return 301 https://%domain%$request_uri;
}

server {
    listen      %ip%:%proxy_port%;
    server_name %domain%;
    return 301 https://%domain%$request_uri;
}

server {
  
  listen      %ip%:%proxy_ssl_port%;
  
  server_name %domain%;
  access_log  /var/log/nginx/app-proxy-%domain%.log main;
  
  ssl on;
  ssl_certificate     %ssl_crt%;
  ssl_certificate_key %ssl_key%;
  ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-RC4-SHA:ECDHE-RSA-AES128-SHA:AES128-GCM-SHA256:RC4:HIGH:!MD5:!aNULL:!EDH:!CAMELLIA;
  ssl_protocols TLSv1.2 TLSv1.1 TLSv1;
  ssl_prefer_server_ciphers on;
  
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
