angular.module('dbChatbot')
  .controller('ChatCtrl', ['$scope', '$routeParams', '$location', '$timeout', '$rootScope',
    'chatService', 'projectService', 'authService',
  function($scope, $routeParams, $location, $timeout, $rootScope, chatService, projectService, authService) {
    var vm = this;
    vm.projectId      = parseInt($routeParams.id);
    vm.messages       = [];
    vm.inputMessage   = '';
    vm.loading        = false;
    vm.schema         = [];
    vm.sidebarOpen    = true;
    vm.schemaPanelOpen = true;
    vm.expandedTables = {};
    vm.currentPage    = {};
    vm.pageSize       = 100;
    vm.user           = authService.getUser();
    vm.project        = null;

    function scrollToBottom() {
      $timeout(function() {
        var el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
      }, 100);
    }

    function highlightAll() {
      $timeout(function() {
        document.querySelectorAll('pre code[data-highlighted!="yes"]').forEach(function(block) {
          hljs.highlightElement(block);
        });
      }, 150);
    }

    function mapHistory(items) {
      return items.map(function(item) {
        var msg = {
          role:           item.role,
          text:           item.message,
          sql_query:      item.sql_query || '',
          tokens_input:   item.tokens_input,
          tokens_output:  item.tokens_output,
          response_time:  item.response_time_ms,
          query_result:   null,
          error:          null
        };
        if (item.query_result) {
          try {
            var qr = typeof item.query_result === 'string' ? JSON.parse(item.query_result) : item.query_result;
            if (qr && qr.error) msg.error = qr.error;
            else msg.query_result = qr;
          } catch(e) {}
        }
        return msg;
      });
    }

    vm.loadHistory = function() {
      chatService.getHistory(vm.projectId)
        .then(function(res) {
          vm.messages = mapHistory(res.data.messages || []);
          vm.project  = res.data.project;
          highlightAll();
          scrollToBottom();
        })
        .catch(function() { $rootScope.showToast('Failed to load history', 'danger'); });
    };

    vm.loadSchema = function() {
      projectService.getSchema(vm.projectId)
        .then(function(res) {
          var raw = res.data.schema || {};
          vm.schema = Object.keys(raw).map(function(name) {
            return {
              name: name,
              columns: raw[name].map(function(c) {
                return { name: c.column, type: c.type, key: c.key };
              })
            };
          });
        })
        .catch(angular.noop);
    };

    vm.sendMessage = function() {
      var text = (vm.inputMessage || '').trim();
      if (!text || vm.loading) return;

      vm.messages.push({ role: 'user', text: text });
      vm.inputMessage = '';
      vm.loading = true;
      scrollToBottom();

      chatService.sendMessage(vm.projectId, text)
        .then(function(res) {
          var d = res.data;
          var msg = {
            role:          'bot',
            text:          d.message,
            sql_query:     d.sql_query || '',
            tokens_input:  d.tokens_input,
            tokens_output: d.tokens_output,
            response_time: d.response_time_ms,
            query_result:  null,
            error:         null
          };
          if (d.query_result) {
            if (d.query_result.error) msg.error = d.query_result.error;
            else msg.query_result = d.query_result;
          }
          vm.messages.push(msg);
          highlightAll();
          scrollToBottom();
        })
        .catch(function(err) {
          var errMsg = (err.data && err.data.error) || 'Something went wrong';
          vm.messages.push({ role: 'bot', text: errMsg, error: null, sql_query: '', query_result: null });
          scrollToBottom();
        })
        .finally(function() { vm.loading = false; });
    };

    vm.handleKeydown = function($event) {
      if ($event.key === 'Enter' && !$event.shiftKey) {
        $event.preventDefault();
        vm.sendMessage();
      }
    };

    vm.getColumns = function(msg) {
      if (!msg.query_result || !msg.query_result.length) return [];
      return Object.keys(msg.query_result[0]);
    };

    vm.getPage = function(msg, idx) {
      var page = vm.currentPage[idx] || 0;
      var start = page * vm.pageSize;
      return (msg.query_result || []).slice(start, start + vm.pageSize);
    };

    vm.totalPages = function(msg) {
      return Math.ceil((msg.query_result || []).length / vm.pageSize);
    };

    vm.prevPage = function(idx) { if ((vm.currentPage[idx] || 0) > 0) vm.currentPage[idx]--; };
    vm.nextPage = function(msg, idx) {
      var cur = vm.currentPage[idx] || 0;
      if (cur < vm.totalPages(msg) - 1) vm.currentPage[idx] = cur + 1;
    };

    vm.toggleTable = function(name) { vm.expandedTables[name] = !vm.expandedTables[name]; };

    vm.goToDashboard = function() { $location.path('/dashboard'); };
    vm.goToTraining  = function() { $location.path('/project/' + vm.projectId + '/training'); };

    vm.logout = function() {
      authService.logout();
      $location.path('/login');
    };

    vm.loadHistory();
    vm.loadSchema();
  }]);
