<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_by_store_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('store_id');
            $table->integer('quantity');
            $table->bigInteger('price');
            $table->bigInteger('subtotal');
            $table->timestamps();

            $table->foreign('transaction_by_store_id')->references('id')->on('transaction_by_stores')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });


        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_transaction_total()
            RETURNS TRIGGER AS $$
            BEGIN
                IF (TG_OP = \'DELETE\') THEN
                    UPDATE transaction_by_stores
                    SET total = (
                        SELECT COALESCE(SUM(subtotal), 0)
                        FROM transaction_items
                        WHERE transaction_by_store_id = OLD.transaction_by_store_id
                        GROUP BY store_id
                    ),
                    updated_at = NOW()
                    WHERE store_id = OLD.store_id;
                ELSE
                    UPDATE transaction_by_stores
                    SET total = (
                        SELECT COALESCE(SUM(subtotal), 0)
                        FROM transaction_items
                        WHERE transaction_by_store_id = OLD.transaction_by_store_id
                        GROUP BY store_id
                    ),
                    updated_at = NOW()
                    WHERE store_id = NEW.store_id;
                END IF;

                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // DB::unprepared('
        //     CREATE OR REPLACE FUNCTION update_cart_items_price()
        //     RETURNS TRIGGER AS $$
        //     BEGIN
        //         UPDATE cart_items
        //         SET price = NEW.quantity * (
        //             SELECT price FROM items WHERE id = NEW.item_id
        //         )
        //         WHERE id = NEW.id;

        //         RETURN NEW;
        //     END;
        //     $$ LANGUAGE plpgsql;
        // ');

        DB::unprepared('
            CREATE TRIGGER transaction_items_update_trigger
            AFTER INSERT OR UPDATE OR DELETE ON transaction_items
            FOR EACH ROW
            EXECUTE FUNCTION update_transaction_total();
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS transaction_items_update_trigger ON transaction_items;');
        DB::unprepared('DROP TRIGGER IF EXISTS update_transaction_item_price_trigger ON transaction_items;');
        DB::unprepared('DROP FUNCTION IF EXISTS update_transaction_total();');
        DB::unprepared('DROP FUNCTION IF EXISTS update_transaction_item_price();');
        Schema::dropIfExists('transaction_items');
    }
};
