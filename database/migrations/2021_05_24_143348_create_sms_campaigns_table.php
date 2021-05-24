<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['otp', 'marketing']);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_campaigns');

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex('sms_logs_batch_id_index');
        });
    }
}
