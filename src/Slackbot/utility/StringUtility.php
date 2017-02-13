<?php

namespace Slackbot\utility;

/**
 * Class StringUtility.
 */
class StringUtility extends AbstractUtility
{
    /**
     * @param $json
     *
     * @throws \Exception
     *
     * @return array|mixed
     */
    public function jsonToArray($json)
    {
        $array = empty($json) ? [] : json_decode($json, true);

        if ($array === null || !is_array($array) || json_last_error() !== 0) {
            throw new \Exception('Invalid JSON content');
        }

        return $array;
    }

    /**
     * @param $toRemove
     * @param $subject
     *
     * @return string
     */
    public function removeStringFromString($toRemove, $subject)
    {
        // pattern: !\s+! is used to replace multiple spaces with single space
        return trim(preg_replace('!\s+!', ' ', str_replace($toRemove, '', $subject)));
    }

    /**
     * @param $toFind
     * @param $subject
     *
     * @return bool
     */
    public function findInString($toFind, $subject)
    {
        $pattern = "@\b{$toFind}\b@";
        if (preg_match($pattern, $subject)) {
            return true;
        }

        return false;
    }

    /**
     * Convert snake case to camel case e.g. admin_user becomes AdminUser.
     *
     * @param $string
     *
     * @return mixed
     */
    public function snakeCaseToCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * Check subject to see whether $string1 is followed by $string2
     *
     * @param $subject
     * @param $string1
     * @param $string2
     * @param array $exceptions
     * @return bool
     */
    public function isString1FollowedByString2($subject, $string1, $string2, $exceptions = [])
    {
        $exceptionsString = '';
        if (!empty($exceptions)) {
            $exceptions = implode('|', $exceptions);
            $exceptionsString = "(?<!{$exceptions})";
        }
        
        $wildCardBetweenTwoStrings = '/'.$string1.'(?:\s+\w+'.$exceptionsString.'){0,2}\s+'.$string2.'\b/';

        if (preg_match($wildCardBetweenTwoStrings, $subject)) {
            return true;
        }

        return false;
    }
}
