<?php
/**
 * Freeform for Craft
 *
 * @package       Solspace:Freeform
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2019, Solspace, Inc.
 * @link          https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\DataObjects;

use Solspace\Commons\Helpers\StringHelper;
use Solspace\Freeform\Library\Exceptions\DataObjects\EmailTemplateException;

class EmailTemplate
{
    const METADATA_PATTERN = "/{#\s*__KEY__:\s*(.*)#}/";

    /** @var string */
    private $name;

    /** @var string */
    private $handle;

    /** @var string */
    private $description;

    /** @var string */
    private $templateData;

    /** @var string */
    private $fromEmail;

    /** @var string */
    private $fromName;

    /** @var string */
    private $cc;

    /** @var string */
    private $bcc;

    /** @var string */
    private $replyToEmail;

    /** @var bool */
    private $includeAttachments;

    /** @var array */
    private $presetAssets = [];

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /**
     * EmailTemplate constructor.
     *
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->templateData = file_get_contents($filePath);

        $this->handle = pathinfo($filePath, PATHINFO_FILENAME);

        $name = $this->getMetadata('templateName');
        if (!$name) {
            $name = StringHelper::camelize(StringHelper::humanize(pathinfo($filePath, PATHINFO_FILENAME)));
        }

        $this->name = $name;

        $this->description  = $this->getMetadata('description');
        $this->fromEmail    = $this->getMetadata('fromEmail', true);
        $this->fromName     = $this->getMetadata('fromName', true);
        $this->cc           = $this->getMetadata('cc');
        $this->bcc          = $this->getMetadata('bcc');
        $this->replyToEmail = $this->getMetadata('replyToEmail');
        $this->subject      = $this->getMetadata('subject', true);
        $this->body         = preg_replace("/{#.*#}\n?/", "", $this->templateData);

        $includeAttachments = $this->getMetadata('includeAttachments');
        $this->includeAttachments = $includeAttachments && strtolower($includeAttachments) === 'true';

        $presetAssets = $this->getMetadata('presetAssets');
        if ($presetAssets) {
            $this->presetAssets = StringHelper::extractSeparatedValues($presetAssets);
        }

        $this->includeAttachments = $includeAttachments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @return string
     */
    public function getReplyToEmail()
    {
        return $this->replyToEmail;
    }

    /**
     * @return bool
     */
    public function isIncludeAttachments()
    {
        return $this->includeAttachments;
    }

    /**
     * @return array
     */
    public function getPresetAssets()
    {
        return $this->presetAssets;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $key
     * @param bool   $required
     *
     * @return null|string
     * @throws EmailTemplateException
     */
    private function getMetadata($key, $required = false)
    {
        $value   = null;
        $pattern = str_replace('__KEY__', $key, self::METADATA_PATTERN);

        if (preg_match($pattern, $this->templateData, $matches)) {
            list ($_, $value) = $matches;
            $value = trim($value);
        } else if ($required) {
            throw new EmailTemplateException(sprintf("Email template does not contain '%s'", $key));
        }

        return $value;
    }
}
