FROM        drupalci/hhvm-base
MAINTAINER  drupalci

# Since we can't inherit from the normal web base, we re-do a bunch of that work here.

ENV DEBIAN_FRONTEND noninteractive
ENV HOME /root

RUN apt-get clean && apt-get update

# Install Apache2 in worker mode
RUN apt-get install -y apache2 apache2-mpm-worker apache2-dev
RUN a2dismod mpm_event && a2enmod mpm_prefork

RUN apt-get clean && apt-get autoremove -y

##
# HHVM 3.7.0
##

# @todo: Add HHVM and Apache config here properly???


