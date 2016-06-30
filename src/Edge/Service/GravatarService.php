<?php

namespace Edge\Service;

class GravatarService
{
    public function hasGravatar($email)
    {
        return !strpos(@get_headers($this->getGravatar($email, '404'))[0], '404');
    }

    public function getGravatar($email, $default = 'identicon')
    {
        return sprintf('https://secure.gravatar.com/avatar/%s?d=%s', md5($email), $default);
    }
}
