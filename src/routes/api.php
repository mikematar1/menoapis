<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessRequestController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\PointsItemController;
use App\Models\BusinessRequest;
use App\Models\Employee;

use function Ramsey\Uuid\v1;

Route::group(["prefix" => "v0.1"], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware(['auth', 'check.banned'])->group(function(){
        Route::middleware(['auth', 'check.user_type'])->group(function () {
            // User Requests
            Route::get('ban/{userid}', [UserController::class, 'banUnbanUser']);
        });
        // Client Requests
        Route::group(["prefix" => "client"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get('/', [ClientController::class, 'getClients']);
                Route::get('count', [ClientController::class, 'getClientsCount']);
            });
            Route::middleware(['auth', 'check.client'])->group(function () {
                Route::post('makeroom', [FirebaseController::class, 'clientMakesRoom']);
                Route::post('sendmsg', [FirebaseController::class, 'clientSendsMessage']);
                Route::get('getrooms', [FirebaseController::class, 'getRoomsForClient']);
                Route::get('getmsgs/{roomid}', [FirebaseController::class, 'getMessagesForClient']);
                Route::post('editinfo', [ClientController::class, 'editInformation']);
                Route::get('search/{name}', [ClientController::class, 'businessSearch']);
                Route::get('favorbusiness/{businessid}', [ClientController::class, 'favnofavBusiness']);
                Route::get('getfavbusinesses', [ClientController::class, 'getFavBusinesses']);
                Route::get('getinfo', [ClientController::class, 'getInformation']);
                Route::get('getreviews', [ClientController::class, 'getReviews']);
                Route::get('gettransactions', [ClientController::class, 'getTransactions']);
            });
        });

        // Employee Requests
        Route::group(['prefix' => 'employee'], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get('/', [EmployeesController::class, 'getEmployees']);
                Route::post('add', [EmployeesController::class, 'addEmployee']);
                Route::post('edit', [EmployeesController::class, 'editInformation']);
                Route::get('ban/{user_id}', [EmployeesController::class, 'banEmployee']);
            });
        });

        // Business Requests
        Route::group(["prefix" => "business"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get("/employee", [BusinessController::class, "getEmployeeBusinesses"]);
                Route::post('add', [BusinessController::class, 'addBusiness']);
                Route::get('delete/{userid}', [BusinessController::class, 'removeBusiness']);
                Route::get('count', [BusinessController::class, 'getBusinessesCount']);
                Route::post('edit/all', [BusinessController::class, 'employeeEditInformation']);
                Route::group(["prefix" => "wallet"], function () {
                    Route::post('add', [BusinessController::class, 'addToWallet']);
                });
                Route::get('deals/{businessid}', [ClientController::class, 'getBusinessDeals']);
                Route::get('/', [BusinessController::class, 'getAllBusinesses']);
            });

            Route::middleware(['auth', 'check.business'])->group(function () {
                Route::post('edit', [BusinessController::class, 'editInformation']);
                Route::get('info', [BusinessController::class, 'getInformation']);
                Route::get('stats', [BusinessController::class, 'getStatistics']);
            });
            Route::middleware(['auth', 'check.client'])->group(function () {
                Route::post('review', [ClientController::class, 'reviewBusiness']);
            });
        });

        // Images Requests
        Route::group(["prefix" => "images"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('add', [ImageController::class, 'addImagesForBusiness']);
                Route::post('edit', [ImageController::class, 'editImagesForBusiness']);
            });
            Route::middleware(['auth', 'check.business'])->group(function () {
                Route::post('addimages', [ImageController::class, 'addImagesForBusiness']);
                Route::post('editimages', [ImageController::class, 'editImagesForBusiness']);
                Route::post('editdeal', [ImageController::class, 'editImagesForDeal']);
            });
            Route::get('getbusinessimages/{businessid}', [ImageController::class, 'getImagesForBusiness']);
            Route::get('getdealimages/{dealid}', [ImageController::class, 'getImagesForDeal']);
        });

        // Business Requests
        Route::group(["prefix" => "request"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                // Route::get('/',[BusinessRequestController::class,'getBusinessRequests']);
                Route::post('verify', [BusinessRequestController::class, 'verifyBusiness']);
                Route::post('assign', [BusinessRequestController::class, 'assignBusiness']);
                Route::get('getbusinessesforreview', [BusinessRequestController::class, 'getBusinessesForReview']);
                Route::get('getbusinessesunderreview', [BusinessRequestController::class, 'getBusinessesUnderReview']);
            });
            Route::post("add", [BusinessRequestController::class, 'addBusinessRequest']);
        });

        // Package Requests
        Route::group(["prefix" => "package"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('add', [PackageController::class, 'addPackage']);
                Route::post('edit', [PackageController::class, 'updatePackage']);
                Route::get('delete/{package_id}', [PackageController::class, 'removePackage']);
            });
            Route::middleware(['auth', 'check.business'])->group(function () {
                Route::get('/', [BusinessController::class, 'getCurrentPackages']);
                Route::get('buy/{packageid}', [BusinessController::class, 'buyPackage']);
            });
            Route::get('/all', [PackageController::class, 'getPackages']);
        });

        // Category Requests
        Route::group(['prefix' => 'category'], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get("/", [CategoryController::class, "getCategoriesWithCount"]);
                Route::post('add', [CategoryController::class, 'addCategory']);
                Route::get('delete/{categoryid}', [CategoryController::class, 'removeCategory']);
            });
        });

        // Todo: Move to the notification controller
        Route::group(["prefix" => "notification"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('sendnotif', [EmployeesController::class, 'sendNotification']);
            });
        });

        // Feedback Requests
        Route::group(["prefix" => "feedback"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get('/', [FeedbackController::class, 'getFeedbacks']);
            });
            Route::post('add', [FeedbackController::class, 'addFeedback']);


        });

        // Testimonial Requests
        Route::group(["prefix" => "testimonial"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('/', [FeedbackController::class, 'getTestimonials']);
                Route::get('add/{feedback_id}', [FeedbackController::class, 'makeFeedbackTestimonial']);
                Route::get('delete/{testimonialid}', [FeedbackController::class, 'removeTestimonial']);
            });
        });

        Route::group(["prefix" => "blog"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('add', [BlogController::class, 'addBlog']);
                Route::get('delete/{blogid}', [BlogController::class, 'removeBlog']);
            });
            Route::get('getblogs', [BlogController::class, 'getBlogs']);
        });

        Route::group(["prefix" => "deal"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get('/count', [DealController::class, 'getDealsCount']);
            });
            Route::middleware(['auth', 'check.business'])->group(function () {

                Route::post('add', [BusinessController::class, 'addDeal']);
                Route::get('delete/{dealid}', [BusinessController::class, 'removeDeal']);
                Route::post('edit', [BusinessController::class, 'editDeal']);
                Route::post('feature', [BusinessController::class, 'featureDeal']);
                Route::get("getfeatured", [BusinessController::class, 'getFeaturedDeals']);
                Route::get('/getbusinessdeals', [BusinessController::class, 'getDeals']);
            });

            Route::post("search", [DealController::class, 'dealSearch']);
            Route::middleware(['auth', 'check.client'])->group(function () {
                Route::get("redeem/{dealid}", [ClientController::class, 'redeemDeal']);
                Route::get("/", [DealController::class, 'getDeals']);
            });


            Route::get('/businessdeals/{businessid}', [DealController::class, 'getBusinessDeals']);
            Route::get("featured", [DealController::class, 'getFeaturedDeals']);

            Route::group(["prefix" => "blog"], function () {
                Route::middleware(['auth', 'check.user_type'])->group(function () {
                    Route::post('add', [BlogController::class, 'addBlog']);
                    Route::get('delete/{blogid}', [BlogController::class, 'removeBlog']);
                });
                Route::get('getblogs', [BlogController::class, 'getBlogs']);
            });
        });

        Route::group(['prefix' => 'search'], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::get('{usertype}/{username}', [EmployeesController::class, 'search']);
            });
        });
        Route::group(["prefix" => "points"], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post("add", [PointsItemController::class, "add"]);
                Route::post("edit", [PointsItemController::class, "edit"]);
                Route::get("remove/{itemid}", [PointsItemController::class, "remove"]);
            });
            Route::middleware(['auth', 'check.business'])->group(function () {
                Route::post("give",[BusinessController::class,"giveClientPoints"]);
            });
            Route::get("get", [ClientController::class, "getPointsItemsPerBusiness"]);
        });
        Route::group(['prefix' => 'gallery'], function () {
            Route::middleware(['auth', 'check.user_type'])->group(function () {
                Route::post('edit', [Photo_Gallery::class, 'addAndRemoveImages']);
            });
            Route::get('get', [Photo_Gallery::class, 'getImages']);
        });
    });
    Route::group(["prefix" => "qrcode"], function () {
        Route::get("getclient/{clientid}", [ClientController::class, 'getClient']);
        Route::get("getdealclient", [ClientController::class, 'getClientAndDeal']);
    });

    Route::group(['prefix' => 'category'], function () {
        Route::get("/all", [CategoryController::class, "getCategories"]);
    });

    Route::group(['prefix' => 'category'], function () {
        Route::get("/all", [CategoryController::class, "getCategories"]);
    });
});
