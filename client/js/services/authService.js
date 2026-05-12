angular.module('dbChatbot')
  .factory('authService', ['$http', 'API_BASE', function($http, API_BASE) {
    return {
      login: function(email, password) {
        return $http.post(API_BASE + '/auth/login.php', { email: email, password: password })
          .then(function(res) {
            localStorage.setItem('jwt_token', res.data.token);
            localStorage.setItem('auth_user', JSON.stringify(res.data.user));
            return res.data;
          });
      },
      register: function(name, email, password) {
        return $http.post(API_BASE + '/auth/register.php', { name: name, email: email, password: password })
          .then(function(res) {
            localStorage.setItem('jwt_token', res.data.token);
            localStorage.setItem('auth_user', JSON.stringify(res.data.user));
            return res.data;
          });
      },
      logout: function() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('auth_user');
      },
      getToken: function() {
        return localStorage.getItem('jwt_token');
      },
      getUser: function() {
        var u = localStorage.getItem('auth_user');
        return u ? JSON.parse(u) : null;
      },
      isLoggedIn: function() {
        return !!localStorage.getItem('jwt_token');
      }
    };
  }]);
