angular.module('dbChatbot', ['ngRoute', 'ngSanitize'])

  .constant('API_BASE', 'http://localhost/sql-chatbot/server/api')

  .config(['$routeProvider', function($routeProvider) {
    $routeProvider
      .when('/login',    { templateUrl: 'views/login.html',    controller: 'AuthCtrl',     controllerAs: 'vm' })
      .when('/register', { templateUrl: 'views/register.html', controller: 'AuthCtrl',     controllerAs: 'vm' })
      .when('/dashboard',{ templateUrl: 'views/dashboard.html',controller: 'ProjectCtrl',  controllerAs: 'vm' })
      .when('/project/:id/chat',     { templateUrl: 'views/chat.html',     controller: 'ChatCtrl',     controllerAs: 'vm' })
      .when('/project/:id/training', { templateUrl: 'views/training.html', controller: 'TrainingCtrl', controllerAs: 'vm' })
      .otherwise({ redirectTo: '/dashboard' });
  }])

  .config(['$httpProvider', function($httpProvider) {
    $httpProvider.interceptors.push('authInterceptor');
  }])

  .factory('authInterceptor', ['$q', '$location', '$rootScope', function($q, $location, $rootScope) {
    return {
      request: function(config) {
        var token = localStorage.getItem('jwt_token');
        if (token) config.headers['Authorization'] = 'Bearer ' + token;
        return config;
      },
      responseError: function(response) {
        if (response.status === 401) {
          localStorage.removeItem('jwt_token');
          localStorage.removeItem('auth_user');
          $rootScope.sessionExpired = true;
          $location.path('/login');
        }
        return $q.reject(response);
      }
    };
  }])

  .run(['$rootScope', '$location', function($rootScope, $location) {
    $rootScope.$on('$routeChangeStart', function(event, next) {
      var publicRoutes = ['/login', '/register'];
      var path = next.$$route ? next.$$route.originalPath : '';
      if (publicRoutes.indexOf(path) === -1 && !localStorage.getItem('jwt_token')) {
        $location.path('/login');
      }
    });

    $rootScope.showToast = function(message, type) {
      $rootScope.toast = { message: message, type: type || 'info' };
      setTimeout(function() {
        $rootScope.$apply(function() { $rootScope.toast = null; });
      }, 3500);
    };
  }]);
