FROM nginx:alpine

RUN apk update && apk add nginx && apk add tzdata busybox-extras \
    && mkdir -p /var/www/app && mkdir -p /run/nginx/

ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

CMD ["nginx", "-g", "daemon off;"]

