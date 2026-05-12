angular.module('dbChatbot')
  .controller('ProjectCtrl', ['$scope', '$location', '$rootScope', 'projectService', 'authService',
  function($scope, $location, $rootScope, projectService, authService) {
    var vm = this;
    vm.projects      = [];
    vm.loading       = false;
    vm.modalMode     = 'create';
    vm.editingId     = null;
    vm.testResult    = null;
    vm.testLoading   = false;
    vm.connectionForm = {
      name: '', description: '',
      db_host: 'localhost', db_port: 3306,
      db_name: '', db_user: '', db_password: ''
    };

    vm.user = authService.getUser();

    function resetForm() {
      vm.connectionForm = {
        name: '', description: '',
        db_host: 'localhost', db_port: 3306,
        db_name: '', db_user: '', db_password: ''
      };
      vm.testResult = null;
      vm.editingId  = null;
    }

    vm.loadProjects = function() {
      vm.loading = true;
      projectService.getProjects()
        .then(function(res) { vm.projects = res.data; })
        .catch(function() { $rootScope.showToast('Failed to load projects', 'danger'); })
        .finally(function() { vm.loading = false; });
    };

    vm.openCreateModal = function() {
      vm.modalMode = 'create';
      resetForm();
      $('#connectionModal').modal('show');
    };

    vm.openEditModal = function(project) {
      vm.modalMode = 'edit';
      vm.editingId = project.id;
      vm.connectionForm = {
        name:        project.name,
        description: project.description || '',
        db_host:     project.db_host || 'localhost',
        db_port:     project.db_port || 3306,
        db_name:     project.db_name || '',
        db_user:     project.db_user || '',
        db_password: ''
      };
      vm.testResult = null;
      $('#connectionModal').modal('show');
    };

    vm.testConnection = function() {
      vm.testLoading = true;
      vm.testResult  = null;
      projectService.testConnection(vm.connectionForm)
        .then(function(res) { vm.testResult = { ok: true, message: res.data.message }; })
        .catch(function(err) { vm.testResult = { ok: false, message: (err.data && err.data.error) || 'Connection failed' }; })
        .finally(function() { vm.testLoading = false; });
    };

    vm.saveProject = function() {
      var action = vm.modalMode === 'create'
        ? projectService.createProject(vm.connectionForm)
        : projectService.updateProject(vm.editingId, vm.connectionForm);

      action
        .then(function() {
          $('#connectionModal').modal('hide');
          $rootScope.showToast(vm.modalMode === 'create' ? 'Project created!' : 'Project updated!', 'success');
          vm.loadProjects();
        })
        .catch(function(err) {
          $rootScope.showToast((err.data && err.data.error) || 'Save failed', 'danger');
        });
    };

    vm.deleteProject = function(id) {
      if (!confirm('Delete this project and all its data?')) return;
      projectService.deleteProject(id)
        .then(function() {
          $rootScope.showToast('Project deleted', 'info');
          vm.loadProjects();
        })
        .catch(function() { $rootScope.showToast('Delete failed', 'danger'); });
    };

    vm.goToChat     = function(id) { $location.path('/project/' + id + '/chat'); };
    vm.goToTraining = function(id) { $location.path('/project/' + id + '/training'); };

    vm.logout = function() {
      authService.logout();
      $location.path('/login');
    };

    vm.loadProjects();
  }]);
