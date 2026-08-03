<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpgradeOTPTokensTable extends Migration
{
    private string $tokenTable;

    public function __construct()
    {
        $this->tokenTable = config('otp.token_table', 'otp_tokens');
    }

    public function up(): void
    {
        if (!Schema::hasTable($this->tokenTable)) {
            return;
        }

        $this->renameRecipientColumn();
        $this->renamePurposeColumn();
        $this->replaceCompositeIndex();
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->tokenTable)) {
            return;
        }

        if (Schema::hasIndex($this->tokenTable, 'otp_recipient_purpose_token_index')) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->dropIndex('otp_recipient_purpose_token_index');
            });
        }

        if (Schema::hasColumn($this->tokenTable, 'recipient')
            && !Schema::hasColumn($this->tokenTable, 'mobile')
        ) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->renameColumn('recipient', 'mobile');
            });
        }

        if (Schema::hasColumn($this->tokenTable, 'purpose')
            && !Schema::hasColumn($this->tokenTable, 'indicator')
        ) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->renameColumn('purpose', 'indicator');
            });
        }

        if (!Schema::hasIndex($this->tokenTable, ['mobile', 'token'])) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->index(['mobile', 'token']);
            });
        }
    }

    private function renameRecipientColumn(): void
    {
        if (Schema::hasColumn($this->tokenTable, 'mobile')
            && !Schema::hasColumn($this->tokenTable, 'recipient')
        ) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->renameColumn('mobile', 'recipient');
            });
        }
    }

    private function renamePurposeColumn(): void
    {
        if (Schema::hasColumn($this->tokenTable, 'indicator')
            && !Schema::hasColumn($this->tokenTable, 'purpose')
        ) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->renameColumn('indicator', 'purpose');
            });

            return;
        }

        if (!Schema::hasColumn($this->tokenTable, 'purpose')) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->string('purpose')
                    ->default(config('otp.prefix', 'otp'));
            });
        }
    }

    private function replaceCompositeIndex(): void
    {
        $oldIndex = $this->tokenTable . '_mobile_token_index';

        if (Schema::hasIndex($this->tokenTable, $oldIndex)) {
            Schema::table($this->tokenTable, function (Blueprint $table) use ($oldIndex): void {
                $table->dropIndex($oldIndex);
            });
        }

        if (!Schema::hasIndex($this->tokenTable, ['recipient', 'purpose', 'token'])) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->index(
                    ['recipient', 'purpose', 'token'],
                    'otp_recipient_purpose_token_index',
                );
            });
        }
    }
}