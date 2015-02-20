var signupForm = angular.module('main', ['ngMessages']);


signupForm.constant('wordpressJS', js);

// create angular controller and pass in $scope and $http
signupForm.controller('signupFormController', [ '$scope', '$http', 'wordpressJS',
    function($scope, $http, wordpressJS) {

        $scope.signupData = {};
        $scope.message = '';
        $scope.submitSignup = function (formData, validity) {
            if (validity) {
                $http({
                    method: 'POST',
                    url: wordpressJS.ajaxurl,
                    data: $.param({
                        action: 'player_signup',
                        security: wordpressJS.security,
                        tournament_id: wordpressJS.post_id,
                        user_id: wordpressJS.user_id,
                        signup_data: formData
                    }),  // pass in data as strings
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}  // set the headers so angular passing info as form data (not request payload)
                })
                    .success(function (data) {

                        if (!data.success) {
                            // if not successful, bind errors to error variables
                            $scope.message = data.data.message;
                            console.log(data.data.message);
                            //$scope.errorSuperhero = data.errors.superheroAlias;
                        } else {
                            $scope.message = data.data.message;
                        }
                    });
            }
            ;
        }
    }
]);
signupForm.directive('input', function ($parse) {
    return {
        restrict: 'E',
        require: '?ngModel',
        link: function (scope, element, attrs) {
            if (attrs.ngModel && attrs.value) {
                $parse(attrs.ngModel).assign(scope, attrs.value);
            }
        }
    };
});

