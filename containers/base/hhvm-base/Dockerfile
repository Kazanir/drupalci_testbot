FROM        drupalci/base
MAINTAINER  drupalci

##
# HipHop PHP Base
##

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

# Add the HHVM source and update

RUN sudo apt-get install -y software-properties-common

RUN sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449
RUN sudo add-apt-repository -y 'deb http://dl.hhvm.com/ubuntu trusty main'
RUN sudo apt-get update -y
RUN sudo apt-get install -y hhvm

RUN chmod 755 /root/

##
# Set HHVM to be the PHP executable of record.
## 

# @todo:

##
# Composer.
##

RUN bash -c "wget http://getcomposer.org/composer.phar && chmod 775 composer.phar && sudo mv composer.phar /usr/local/bin/composer"

# Drush and dependencies
RUN HOME=/ /usr/local/bin/composer global require drush/drush:dev-master
RUN /.composer/vendor/drush/drush/drush --version

# Supervisor
COPY ./conf/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Scripts.
COPY ./conf/scripts/start.sh /start.sh
COPY ./conf/mongodb.settings.php /mongodb.settings.php
COPY ./conf/scripts/foreground.sh /etc/apache2/foreground.sh

# Make start.sh executable.
RUN chmod 755 /start.sh


