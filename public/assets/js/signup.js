var signupForm = angular.module('main', []);


signupForm.constant('wordpressJS', js);

// create angular controller and pass in $scope and $http
signupForm.controller('signupFormController', [ '$scope', '$http', 'wordpressJS',
    function($scope, $http, wordpressJS) {

        $scope.order = {};
        $scope.packages = {};
        $scope.services = {};
        $scope.ordered = false;
        $scope.message = '';
        $scope.selected = '';


        $scope.processSignup = function () {
            $http({
                method: 'POST',
                url: wordpressJS.ajaxurl,
                data: $.param({
                    action: 'place_order',
                    security: wordpressJS.security
                }),  // pass in data as strings
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}  // set the headers so angular passing info as form data (not request payload)
            })
                .success(function (data) {

                    if (!data.success) {
                        // if not successful, bind errors to error variables
                        $scope.errorName = data.errors.name;
                        $scope.errorSuperhero = data.errors.superheroAlias;
                    } else {
                        $scope.message = data.data.message;
                        $scope.ordered = true;
                    }
                });
        };


    }
]);
