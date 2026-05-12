angular.module('dbChatbot')
  .controller('AuthCtrl', ['$scope', '$location', '$rootScope', 'authService', function($scope, $location, $rootScope, authService) {
    var vm = this;
    vm.loginForm     = { email: '', password: '' };
    vm.registerForm  = { name: '', email: '', password: '' };
    vm.loading       = false;
    vm.error         = '';
    vm.showPassword  = false;

    if (authService.isLoggedIn()) {
      $location.path('/dashboard');
      return;
    }

    vm.isLogin = function() {
      return $location.path() === '/login';
    };

    vm.togglePassword = function() {
      vm.showPassword = !vm.showPassword;
    };

    vm.login = function() {
      vm.error = '';
      vm.loading = true;
      authService.login(vm.loginForm.email, vm.loginForm.password)
        .then(function() {
          $rootScope.showToast('Welcome back!', 'success');
          $location.path('/dashboard');
        })
        .catch(function(err) {
          vm.error = (err.data && err.data.error) || 'Login failed';
        })
        .finally(function() { vm.loading = false; });
    };

    vm.register = function() {
      vm.error = '';
      vm.loading = true;
      authService.register(vm.registerForm.name, vm.registerForm.email, vm.registerForm.password)
        .then(function() {
          $rootScope.showToast('Account created!', 'success');
          $location.path('/dashboard');
        })
        .catch(function(err) {
          vm.error = (err.data && err.data.error) || 'Registration failed';
        })
        .finally(function() { vm.loading = false; });
    };
  }]);
