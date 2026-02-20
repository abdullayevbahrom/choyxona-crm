<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::pattern("order", "[0-9]+");
Route::pattern("item", "[0-9]+");
Route::pattern("bill", "[0-9]+");

Route::get("/", function () {
    return auth()->check()
        ? redirect()->route("dashboard")
        : redirect()->route("login");
});

Route::get("/healthz", HealthController::class)->name("healthz");

Route::middleware("auth")->group(function () {
    Route::get("/profile", [ProfileController::class, "edit"])->name(
        "profile.edit",
    );
    Route::patch("/profile", [ProfileController::class, "update"])->name(
        "profile.update",
    );
    Route::delete("/profile", [ProfileController::class, "destroy"])->name(
        "profile.destroy",
    );

    Route::middleware("role:admin,manager,cashier,waiter")->group(function () {
        Route::get("/dashboard", [RoomController::class, "dashboard"])->name(
            "dashboard",
        );
        Route::get("/dashboard/cards", [
            RoomController::class,
            "dashboardCards",
        ])->name("dashboard.cards");
        Route::get("/dashboard/fingerprint", [
            RoomController::class,
            "dashboardFingerprint",
        ])->name("dashboard.fingerprint");
        Route::get("/orders/create", [OrderController::class, "create"])->name(
            "orders.create",
        );
        Route::get("/orders/create/status", [
            OrderController::class,
            "createStatus",
        ])->name("orders.create.status");
        Route::get("/orders/create/status-fingerprint", [
            OrderController::class,
            "createStatusFingerprint",
        ])->name("orders.create.status-fingerprint");
        Route::post("/orders", [OrderController::class, "store"])->name(
            "orders.store",
        );
        Route::get("/orders/{order}", [OrderController::class, "show"])->name(
            "orders.show",
        );
        Route::get("/orders/{order}/panel", [
            OrderController::class,
            "panel",
        ])->name("orders.panel");
        Route::get("/orders/{order}/panel-fingerprint", [
            OrderController::class,
            "panelFingerprint",
        ])->name("orders.panel-fingerprint");
        Route::post("/orders/{order}/items", [
            OrderController::class,
            "addItem",
        ])->name("orders.items.store");
        Route::patch("/orders/{order}/items/{item}", [
            OrderController::class,
            "updateItem",
        ])->name("orders.items.update");
        Route::delete("/orders/{order}/items/{item}", [
            OrderController::class,
            "removeItem",
        ])->name("orders.items.destroy");
    });

    Route::middleware("role:admin,manager,cashier")->group(function () {
        Route::get("/orders/history", [
            OrderController::class,
            "history",
        ])->name("orders.history");

        Route::post("/orders/{order}/cancel", [
            OrderController::class,
            "cancel",
        ])->name("orders.cancel");

        Route::post("/orders/{order}/bill", [
            BillController::class,
            "store",
        ])->name("orders.bill.store");
        Route::get("/bills/{bill}", [BillController::class, "show"])->name(
            "bills.show",
        );
        Route::get("/bills/{bill}/pdf", [BillController::class, "pdf"])->name(
            "bills.pdf",
        );
        Route::post("/bills/{bill}/print", [
            BillController::class,
            "print",
        ])->name("bills.print");
    });

    Route::middleware("role:admin,manager")->group(function () {
        Route::get("/rooms", [RoomController::class, "index"])->name(
            "rooms.index",
        );
        Route::post("/rooms", [RoomController::class, "store"])->name(
            "rooms.store",
        );
        Route::patch("/rooms/{room}", [RoomController::class, "update"])->name(
            "rooms.update",
        );
        Route::post("/rooms/{room}/toggle-active", [
            RoomController::class,
            "toggleActive",
        ])->name("rooms.toggle-active");

        Route::get("/menu", [MenuController::class, "index"])->name(
            "menu.index",
        );
        Route::post("/menu", [MenuController::class, "store"])->name(
            "menu.store",
        );
        Route::patch("/menu/{menuItem}", [
            MenuController::class,
            "update",
        ])->name("menu.update");
        Route::post("/menu/{menuItem}/toggle-active", [
            MenuController::class,
            "toggleActive",
        ])->name("menu.toggle-active");

        Route::get("/reports", [ReportController::class, "index"])->name(
            "reports.index",
        );
        Route::get("/reports/export.csv", [
            ReportController::class,
            "exportCsv",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.export.csv");
        Route::get("/reports/export.xls", [
            ReportController::class,
            "exportXls",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.export.xls");
        Route::get("/reports/export.pdf", [
            ReportController::class,
            "exportPdf",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.export.pdf");
        Route::post("/reports/exports", [
            ReportController::class,
            "requestExport",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.exports.request");
        Route::get("/reports/exports/statuses", [
            ReportController::class,
            "exportStatuses",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.exports.statuses");
        Route::get("/reports/exports/{export}", [
            ReportController::class,
            "downloadExport",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.exports.download");
        Route::get("/reports/exports/{export}/status", [
            ReportController::class,
            "exportStatus",
        ])
            ->middleware("throttle:reports-export")
            ->name("reports.exports.status");
        Route::get("/settings", [SettingController::class, "index"])->name(
            "settings.index",
        );
        Route::patch("/settings", [SettingController::class, "update"])->name(
            "settings.update",
        );
    });

    Route::middleware("role:admin")->group(function () {
        Route::get("/users", [UserController::class, "index"])->name(
            "users.index",
        );
        Route::post("/users", [UserController::class, "store"])->name(
            "users.store",
        );
        Route::patch("/users/{user}", [UserController::class, "update"])->name(
            "users.update",
        );

        Route::get("/activity-logs", [
            ActivityLogController::class,
            "index",
        ])->name("activity-logs.index");
        Route::post("/activity-logs/exports", [
            ActivityLogController::class,
            "requestExport",
        ])
            ->middleware("throttle:activity-export")
            ->name("activity-logs.exports.request");
        Route::get("/activity-logs/exports/{export}", [
            ActivityLogController::class,
            "downloadExport",
        ])
            ->middleware("throttle:activity-export")
            ->name("activity-logs.exports.download");
        Route::get("/activity-logs/export.csv", [
            ActivityLogController::class,
            "exportCsv",
        ])
            ->middleware("throttle:activity-export")
            ->name("activity-logs.export");
    });
});

require __DIR__ . "/auth.php";
