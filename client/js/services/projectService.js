angular.module('dbChatbot')
  .factory('projectService', ['$http', 'API_BASE', function($http, API_BASE) {
    return {
      getProjects: function() {
        return $http.get(API_BASE + '/projects/index.php');
      },
      createProject: function(data) {
        return $http.post(API_BASE + '/projects/index.php', data);
      },
      updateProject: function(id, data) {
        return $http.put(API_BASE + '/projects/update.php?id=' + id, data);
      },
      deleteProject: function(id) {
        return $http.delete(API_BASE + '/projects/delete.php?id=' + id);
      },
      testConnection: function(data) {
        return $http.post(API_BASE + '/projects/test-connection.php', data);
      },
      getSchema: function(projectId) {
        return $http.get(API_BASE + '/projects/schema.php?project_id=' + projectId);
      }
    };
  }]);
