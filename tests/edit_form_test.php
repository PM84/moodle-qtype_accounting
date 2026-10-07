<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace qtype_accounting;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/accounting/edit_accounting_form.php');

/**
 * Tests for the accounting editing form's account data.
 *
 * @package    qtype_accounting
 * @copyright  2026 Hochschule Flensburg / lambda9
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_accounting_edit_form::get_all_accounts_by_chart
 */
final class edit_form_test extends \advanced_testcase {
    /**
     * Read the account data without constructing the unrelated form UI.
     *
     * @param \context $context The form context.
     * @return array Accounts keyed by chart and account IDs.
     */
    private function get_accounts(\context $context): array {
        $form = $this->getMockBuilder(\qtype_accounting_edit_form::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $form->context = $context;
        $method = new \ReflectionMethod(\qtype_accounting_edit_form::class, 'get_all_accounts_by_chart');
        $method->setAccessible(true);
        return $method->invoke($form);
    }

    /**
     * Preserve chart order, account-name order, empty charts, IDs, and course isolation.
     */
    public function test_accounts_grouped_and_sorted_in_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $zchart = chart_manager::create_chart('Zulu', $context->id);
        $emptychart = chart_manager::create_chart('Empty', $context->id);
        $achart = chart_manager::create_chart('Alpha', $context->id);
        $zaccount = account_manager::add($achart, 'Zulu account', 0);
        $aaccount = account_manager::add($achart, 'Alpha account', 1);
        $otheraccount = account_manager::add($zchart, 'Alpha account', 0);
        $outsidechart = chart_manager::create_chart('Outside', \context_system::instance()->id);
        account_manager::add($outsidechart, 'Hidden account', 0);

        $this->assertSame([
            $achart => [$aaccount => 'Alpha account', $zaccount => 'Zulu account'],
            $emptychart => [],
            $zchart => [$otheraccount => 'Alpha account'],
        ], $this->get_accounts($context));
    }

    /**
     * No course charts or a context without a course must produce an empty result.
     */
    public function test_no_available_charts(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], $this->get_accounts(\context_course::instance($course->id)));
        $chartid = chart_manager::create_chart('System chart', \context_system::instance()->id);
        account_manager::add($chartid, 'Bank');
        $this->assertSame([], $this->get_accounts(\context_system::instance()));
    }

    /**
     * Loading accounts must use one chart read and one account read, regardless of chart count.
     */
    public function test_accounts_use_bounded_reads(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        for ($i = 0; $i < 10; $i++) {
            $chartid = chart_manager::create_chart('Chart ' . $i, $context->id);
            account_manager::add($chartid, 'Bank');
        }
        $before = $DB->perf_get_reads();
        $accounts = $this->get_accounts($context);
        $reads = $DB->perf_get_reads() - $before;
        $this->assertCount(10, $accounts);
        $this->assertEquals(2, $reads);
    }
}
