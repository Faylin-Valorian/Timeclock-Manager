<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\IDBConnection;

class Version20260214170000 extends SimpleMigrationStep {

    /** @var IDBConnection */
    private $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function name(): string {
        return 'Initial Installation - Schema & Data';
    }

    public function description(): string {
        return 'Creates tables and seeds initial data (States, Counties, Jobs, and Access Rules)';
    }

    /**
     * Step 1: Create Database Tables
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // 1. Core Timesheets Table (tm_timesheets)
        if (!$schema->hasTable('tm_timesheets')) {
            $table = $schema->createTable('tm_timesheets');
            $table->addColumn('timesheet_id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('userid', 'string', ['length' => 64, 'notnull' => true]);
            $table->addColumn('timesheet_date', 'date', ['notnull' => true]);
            $table->addColumn('time_in', 'time', ['notnull' => false]);
            $table->addColumn('time_out', 'time', ['notnull' => false]);
            $table->addColumn('time_break', 'integer', ['default' => 0]);
            $table->addColumn('time_total', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0]);
            $table->addColumn('travel', 'integer', ['default' => 0]);
            $table->addColumn('travel_road_scanning', 'integer', ['notnull' => false, 'default' => 0]);
            $table->addColumn('travel_first_last_day', 'integer', ['notnull' => false, 'default' => 0]);
            $table->addColumn('travel_overnight', 'integer', ['notnull' => false, 'default' => 0]);
            $table->addColumn('travel_per_diem', 'integer', ['default' => 0]);
            $table->addColumn('travel_state', 'string', ['length' => 10, 'notnull' => false]);
            $table->addColumn('travel_county', 'string', ['length' => 100, 'notnull' => false]);
            $table->addColumn('travel_miles', 'integer', ['default' => 0]);
            $table->addColumn('travel_extra_expenses', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0]);
            $table->addColumn('additional_comments', 'text', ['notnull' => false]);
            $table->addColumn('archive', 'integer', ['default' => 0]);
            $table->setPrimaryKey(['timesheet_id']);
            $table->addIndex(['userid'], 'idx_tm_ts_user');
        }

        // 2. Activities Table (tm_activity)
        if (!$schema->hasTable('tm_activity')) {
            $table = $schema->createTable('tm_activity');
            $table->addColumn('activity_id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('timesheet_id', 'integer', ['notnull' => true]);
            $table->addColumn('activity_description', 'string', ['length' => 255]);
            $table->addColumn('activity_percent', 'integer', ['default' => 0]);
            $table->setPrimaryKey(['activity_id']);
            $table->addIndex(['timesheet_id'], 'idx_tm_act_ts');
        }

        // 3. Jobs Table (tm_jobs)
        if (!$schema->hasTable('tm_jobs')) {
            $table = $schema->createTable('tm_jobs');
            $table->addColumn('job_id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('job_name', 'string', ['length' => 255]);
            $table->addColumn('job_description', 'text', ['notnull' => false, 'length' => 1024, 'default' => '']);
            $table->addColumn('job_archive', 'integer', ['default' => 0]);
            $table->addColumn('job_archived_at', 'datetime', ['notnull' => false]);
            $table->addColumn('is_pto', 'integer', ['default' => 0]);
            $table->addColumn('job_revenue', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0]);
            $table->addColumn('job_expense_budget', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0]);
            $table->addColumn('job_hourly_cost', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0]);
            $table->setPrimaryKey(['job_id']);
        }

        // 4. Access Rules Table (tm_access_rules)
        if (!$schema->hasTable('tm_access_rules')) {
            $table = $schema->createTable('tm_access_rules');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('rule_key', 'string', ['length' => 64]);
            $table->addColumn('allowed_groups', 'text', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['rule_key'], 'tm_access_idx');
        }

        // 5. Admin Settings Table (tm_admin_settings)
        if (!$schema->hasTable('tm_admin_settings')) {
            $table = $schema->createTable('tm_admin_settings');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('setting_key', 'string', ['length' => 64]);
            $table->addColumn('setting_value', 'text', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['setting_key'], 'tm_settings_idx');
        }

        // 6. US States Table (tm_states)
        if (!$schema->hasTable('tm_states')) {
            $table = $schema->createTable('tm_states');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('state_name', 'string', ['length' => 100, 'notnull' => true]);
            $table->addColumn('state_abbr', 'string', ['length' => 10, 'notnull' => true]);
            $table->addColumn('fips_code', 'string', ['length' => 10, 'notnull' => true]);
            $table->addColumn('is_enabled', 'integer', ['default' => 1]);
            $table->setPrimaryKey(['id']);
        }

        // 7. US Counties Table (tm_counties)
        if (!$schema->hasTable('tm_counties')) {
            $table = $schema->createTable('tm_counties');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('county_name', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('state_fips', 'string', ['length' => 10, 'notnull' => true]);
            $table->addColumn('is_enabled', 'integer', ['default' => 1]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['state_fips'], 'idx_tm_counties_fips');
        }

        // 8. Holidays Table (tm_holidays)
        if (!$schema->hasTable('tm_holidays')) {
            $table = $schema->createTable('tm_holidays');
            $table->addColumn('holiday_id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('holiday_name', 'string', ['length' => 255]);
            $table->addColumn('holiday_start_date', 'date');
            $table->addColumn('holiday_end_date', 'date');
            $table->addColumn('holiday_bg', 'string', ['length' => 255, 'default' => '']);
            $table->addColumn('holiday_archive', 'integer', ['default' => 0]);
            $table->setPrimaryKey(['holiday_id']);
        }

        return $schema;
    }
}