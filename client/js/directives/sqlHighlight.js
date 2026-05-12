angular.module('dbChatbot')
  .directive('sqlHighlight', ['$timeout', function($timeout) {
    return {
      restrict: 'A',
      link: function(scope, element, attrs) {
        attrs.$observe('sqlHighlight', function(value) {
          if (value) {
            element[0].textContent = value;
            $timeout(function() { hljs.highlightElement(element[0]); }, 0);
          }
        });
      }
    };
  }]);
