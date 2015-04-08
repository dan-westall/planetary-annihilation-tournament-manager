var signupForm = angular.module('main', ['ngMessages', 'ngSanitize', 'angucomplete-alt']);


signupForm.constant('wordpressJS', js);
signupForm.constant('clanList', clansListing);

// create angular controller and pass in $scope and $http
signupForm.controller('signupFormController', [ '$scope', '$http', 'wordpressJS', 'clanList',
    function($scope, $http, wordpressJS, clanList) {

        $scope.signupData = {};
        $scope.result = {};
        $scope.clans = {};

        if(clanList.length){
            $scope.clanList = clanList;
        }

        $scope.setClan = function (clan){
            $scope.signupData.clanName = clan.title;
        }

        $scope.submission = false;
        $scope.submitSignup = function (formData, validity) {
            if (validity) {
                $scope.submission = true;
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
                    $scope.submission = false;
                    if (!data.success) {
                        $scope.result.message = data.data.message;
                        $scope.result.type = data.data.type;
                    } else {
                        $scope.result.message = data.data.message;
                        $scope.result.type = data.data.type;
                    }
                });
            };
        };
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

