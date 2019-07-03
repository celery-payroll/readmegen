<?php namespace ReadmeGen\Log;

use ReadmeGen\Vcs\Type\AbstractType;

/**
 * Log extractor.
 *
 * Filters the parsed log and returns grouped messages.
 */
class Extractor {

    /**
     * The log.
     *
     * @var array
     */
    protected $log = array();

    /**
     * Message groups.
     *
     * @var array
     */
    protected $messageGroups = array();

    /**
     * Message groups as a string.
     *
     * @var string
     */
    protected $messageGroupsJoined;

    /**
     * Grouped messages.
     *
     * @var array
     */
    protected $groups = array();

    /**
     * Log setter.
     *
     * @param array $log
     * @return $this
     */
    public function setLog(array $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Message groups setter.
     *
     * @param array $messageGroups
     * @return $this
     */
    public function setMessageGroups(array $messageGroups)
    {
        $this->messageGroups = $messageGroups;

        // Set the joined message groups as well
        foreach ($this->messageGroups as $header => $keywords) {
            $this->messageGroupsJoined[$header] = join('|', $keywords);
        }

        return $this;
    }

    /**
     * Groups messages and returns them.
     *
     * @return array
     */
    public function extract() {
        foreach ($this->log as $line) {
            foreach ($this->messageGroupsJoined as $header => $keywords) {
                $pattern = $this->getPattern($keywords);

                if (preg_match($pattern, $line)) {
                    $scope = $this->getScope($line, $keywords);
                    $this->appendToGroup($header, $line, $pattern, $scope);
                }
            }
        }

        // Remove empty groups
        foreach (array_keys($this->messageGroups) as $groupKey) {
            if (true === empty($this->groups[$groupKey])) {
                unset($this->messageGroups[$groupKey]);
            }
        }

        // The array_merge sorts $messageGroups basing on $groups
        return array_merge($this->messageGroups, $this->groups);
    }

    /**
     * Appends a message to a group
     *
     * @param string $groupHeader
     * @param string $text
     * @param string $pattern
     */
    protected function appendToGroup($groupHeader, $text, $pattern, $scope = null) {
        $cleanEntry = trim(preg_replace($pattern, '', $text));
        if (!empty($scope)) {
            $cleanEntry = $scope . AbstractType::SCOPE_SEPARATOR . $cleanEntry;
        }

        $this->groups[$groupHeader][] = $cleanEntry;
    }

    /**
     * Returns the regexp pattern used to determine the log entry's group.
     *
     * @param string $keywords
     * @return string
     */
    protected function getPattern($keywords) {
        return '/(^('.$keywords.')?\([^()]*[^()]*\):)|(^('.$keywords.'):)/i';
    }

    /**
     * Returns the regexp pattern used to determine the log entry's scope.
     *
     * @param string $keywords
     * @return string
     */
    protected function getScopePattern($keywords) {
        return '/^('.$keywords.')?\(([^()]*[^()]*)\):/i';
    }

    /**
     * Returns the optional scope from the log entry.
     *
     * @param string $text
     * @param string $keywords
     * @return null|string
     */
    protected function getScope($text, $keywords)
    {
        $strReturn = null;

        preg_match($this->getScopePattern($keywords), $text, $matches);

        if (count($matches) === 3 && !empty($matches[2])) {
            $strReturn = trim($matches[2]);
        }

        return $strReturn;
    }
}
