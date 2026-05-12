angular.module('dbChatbot')
  .controller('TrainingCtrl', ['$scope', '$routeParams', '$location', '$rootScope',
    'trainingService', 'authService',
  function($scope, $routeParams, $location, $rootScope, trainingService, authService) {
    var vm = this;
    vm.projectId    = parseInt($routeParams.id);
    vm.chatMessages = [];
    vm.pairs        = [];
    vm.inputMessage = '';
    vm.loading      = false;
    vm.filterStatus = '';
    vm.editingPair  = null;
    vm.user         = authService.getUser();

    vm.chatMessages.push({
      role: 'bot',
      text: 'Ask me a question about your data and I\'ll generate a SQL training pair for you.'
    });

    vm.loadPairs = function() {
      trainingService.getPairs(vm.projectId, vm.filterStatus)
        .then(function(res) { vm.pairs = res.data; })
        .catch(function() { $rootScope.showToast('Failed to load training pairs', 'danger'); });
    };

    vm.generatePair = function() {
      var text = (vm.inputMessage || '').trim();
      if (!text || vm.loading) return;

      vm.chatMessages.push({ role: 'user', text: text });
      vm.inputMessage = '';
      vm.loading = true;

      trainingService.generatePair(vm.projectId, text)
        .then(function(res) {
          var d = res.data;
          vm.chatMessages.push({
            role:      'bot',
            text:      d.explanation || 'SQL generated.',
            sql_query: d.sql_query,
            pairId:    d.id,
            status:    d.status
          });
          vm.pairs.unshift(d);
        })
        .catch(function(err) {
          var errMsg = (err.data && err.data.error) || 'Generation failed';
          vm.chatMessages.push({ role: 'bot', text: errMsg });
        })
        .finally(function() { vm.loading = false; });
    };

    vm.handleKeydown = function($event) {
      if ($event.key === 'Enter' && !$event.shiftKey) {
        $event.preventDefault();
        vm.generatePair();
      }
    };

    vm.setStatus = function(pair, status) {
      trainingService.approvePair(pair.id, status)
        .then(function() {
          pair.status = status;
          $rootScope.showToast('Status updated to ' + status, 'success');
        })
        .catch(function() { $rootScope.showToast('Update failed', 'danger'); });
    };

    vm.startEdit = function(pair) {
      vm.editingPair = {
        id:          pair.id,
        question:    pair.question,
        sql_query:   pair.sql_query,
        explanation: pair.explanation || '',
        _original:   pair
      };
    };

    vm.cancelEdit = function() { vm.editingPair = null; };

    vm.saveEdit = function() {
      var ep = vm.editingPair;
      trainingService.approvePair(ep.id, ep._original.status, {
        question: ep.question, sql_query: ep.sql_query, explanation: ep.explanation
      }).then(function() {
        ep._original.question    = ep.question;
        ep._original.sql_query   = ep.sql_query;
        ep._original.explanation = ep.explanation;
        vm.editingPair = null;
        $rootScope.showToast('Pair updated', 'success');
      }).catch(function() { $rootScope.showToast('Update failed', 'danger'); });
    };

    vm.exportPairs = function() {
      window.location.href = trainingService.exportUrl(vm.projectId);
    };

    vm.goToDashboard = function() { $location.path('/dashboard'); };
    vm.goToChat      = function() { $location.path('/project/' + vm.projectId + '/chat'); };

    vm.logout = function() {
      authService.logout();
      $location.path('/login');
    };

    vm.loadPairs();
  }]);
