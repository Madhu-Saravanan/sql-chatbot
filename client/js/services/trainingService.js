angular.module('dbChatbot')
  .factory('trainingService', ['$http', 'API_BASE', 'authService', function($http, API_BASE, authService) {
    return {
      getPairs: function(projectId, status) {
        var url = API_BASE + '/training/index.php?project_id=' + projectId;
        if (status) url += '&status=' + status;
        return $http.get(url);
      },
      generatePair: function(projectId, question) {
        return $http.post(API_BASE + '/training/generate.php', { project_id: projectId, question: question });
      },
      approvePair: function(id, status, editedData) {
        var data = angular.extend({ id: id, status: status }, editedData || {});
        return $http.post(API_BASE + '/training/approve.php', data);
      },
      exportUrl: function(projectId) {
        return API_BASE + '/training/export.php?project_id=' + projectId + '&token=' + authService.getToken();
      }
    };
  }]);
