<?php
namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\ContentType\Command;

/**
 *
 */
class Validators
{
    public static function validateShortcutNotation($shortcut) {
        if (!preg_match("/^[a-zA-Z0-9]{1,}Bundle:[a-zA-Z0-9]{1,}$/", $shortcut)) {
            throw new \InvalidArgumentException(
                'The configuration file name should be something like AcmeBlogBundle:filename'
            );
        }
        return $shortcut;
    }
}
