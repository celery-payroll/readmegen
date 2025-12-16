<?php

namespace spec\ReadmeGen\Output\Format;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MdSpec extends ObjectBehavior
{
    protected $issueTrackerUrl = 'http://some.issue.tracker.com/show/';
    protected $issueTrackerPattern = 'http://some.issue.tracker.com/show/\1';
    protected $log = array(
        'Features' => array(
            'bar #123 baz',
            'dummy feature',
        ),
        'Bugfixes' => array(
            'some bugfix (#890)',
        ),
    );

    function let() {
        $this->setLog($this->log);
    }

    function it_should_add_links_to_the_issue_tracker()
    {
        $result = array(
            'Features' => array(
                "bar [#123]({$this->issueTrackerUrl}123) baz",
                'dummy feature',
            ),
            'Bugfixes' => array(
                "some bugfix ([#890]({$this->issueTrackerUrl}890))",
            ),
        );

        $this->setIssueTrackerUrlPattern($this->issueTrackerPattern);
        $this->decorate()->shouldReturn($result);
    }

    function it_should_generate_a_write_ready_output() {
        $this->setRelease('4.5.6')
            ->setDate(new \DateTime('2014-12-21'));

        $result = array(
            "## 4.5.6",
            "*(2014-12-21)*",
            "\n#### Features",
            '* bar #123 baz',
            '* dummy feature',
            "\n#### Bugfixes",
            '* some bugfix (#890)',
            "\n---\n",
        );

        $this->generate()->shouldReturn($result);
    }

    function it_should_not_deduplicate_issues_by_default()
    {
        $log = array(
            'Features' => array(
                'first feature #123',
                'second feature #123',
            ),
        );

        $this->setLog($log);
        $this->setIssueTrackerUrlPattern($this->issueTrackerPattern);

        $result = $this->decorate();

        $result['Features'][0]->shouldContain('#123');
        $result['Features'][1]->shouldContain('#123');
    }

    function it_should_remove_entire_entry_when_unique_issues_enabled()
    {
        $log = array(
            'Features' => array(
                'first feature #123',
                'second feature #123',
            ),
        );

        $this->setLog($log);
        $this->setIssueTrackerUrlPattern($this->issueTrackerPattern);
        $this->setUniqueIssues(true);

        $result = $this->decorate();

        // First entry should remain
        $result['Features'][0]->shouldContain('#123');
        // Second entry should be removed entirely, so only 1 entry remains
        $result['Features']->shouldHaveCount(1);
    }

    function it_should_remove_entry_from_lower_priority_group()
    {
        $log = array(
            'Features' => array(
                'feature #123',
            ),
            'Bugfixes' => array(
                'bugfix #123',
            ),
        );

        $this->setLog($log);
        $this->setIssueTrackerUrlPattern($this->issueTrackerPattern);
        $this->setUniqueIssues(true);

        $result = $this->decorate();

        // Features entry should remain
        $result['Features'][0]->shouldContain('#123');
        // Bugfixes group should be removed entirely (empty)
        $result->shouldNotHaveKey('Bugfixes');
    }

    function it_should_remove_entries_with_any_duplicate_issue()
    {
        $log = array(
            'Features' => array(
                'feature #100 #200',
                'another #200 #300',
                'third #100',
            ),
        );

        $this->setLog($log);
        $this->setIssueTrackerUrlPattern($this->issueTrackerPattern);
        $this->setUniqueIssues(true);

        $result = $this->decorate();

        // First entry keeps both #100 and #200
        $result['Features'][0]->shouldContain('#100');
        $result['Features'][0]->shouldContain('#200');

        // Second and third entries are removed because they contain duplicate issues
        // Only 1 entry should remain
        $result['Features']->shouldHaveCount(1);
    }
}
