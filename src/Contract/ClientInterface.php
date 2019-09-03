<?php
/**
 * Created by PhpStorm.
 * User: AlexLiu
 * Date: 2019-09-02
 * Time: 17:11
 */

namespace Alex\ShortLink\Contract;


interface ClientInterface
{
    /**
     * @param $sourceUrl
     * @return mixed
     */
    public function getShortUrl($sourceUrl);

    /**
     * @return mixed
     */
    public function setShortUrl();
}
