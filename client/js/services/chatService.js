angular.module('dbChatbot')
  .factory('chatService', ['$http', 'API_BASE', function($http, API_BASE) {
    return {
      getHistory: function(projectId) {
        return $http.get(API_BASE + '/chat/index.php?project_id=' + projectId);
      },
      sendMessage: function(projectId, message) {
        return $http.post(API_BASE + '/chat/send.php', { project_id: projectId, message: message });
      }
    };
  }]);
