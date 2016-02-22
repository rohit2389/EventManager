
app.controller('eventsCtrl', function ($scope, $modal, $filter, Data) {
    $scope.event = {};

    Data.get('monthevents').then(function(data){
        $scope.monthevents = data.data;
    });

    Data.get('events').then(function(data){
        $scope.events = data.data;
    });

    $scope.changeEventStatus = function(event){
        event.status = (event.status=="Active" ? "Inactive" : "Active");
        Data.put("approve/"+event.event_id,{status:event.status});
    };
    $scope.deleteProduct = function(event){
        if(confirm("Are you sure to remove the event")){
            Data.delete("events/"+event.event_id).then(function(result){
                $scope.events = _.without($scope.events, _.findWhere($scope.events, {event_id:event.event_id}));
            });
        }
    };
    $scope.open = function (p,size) {
        var modalInstance = $modal.open({
          templateUrl: 'partials/eventEdit.html',
          controller: 'eventEditCtrl',
          size: size,
          resolve: {
            item: function () {
              return p;
            }
          }
        });
        modalInstance.result.then(function(selectedObject) {
            if(selectedObject.save == "insert"){
                $scope.events.push(selectedObject);
                $scope.events = $filter('orderBy')($scope.events, 'event_id', 'reverse');
            }else if(selectedObject.save == "update"){
                p.event = selectedObject.event;
                p.event_start_datetime = selectedObject.event_start_datetime;
                p.event_end_datetime = selectedObject.event_end_datetime;
            }
        });
    };
    
 $scope.columns = [
                    {text:"Event ID",predicate:"event_id",sortable:true,dataType:"number"},
                    {text:"Event Title",predicate:"event",sortable:true},
                    {text:"Event Start",predicate:"event_start_datetime",sortable:true},
                    {text:"Event End",predicate:"event_end_datetime",sortable:true},
                    {text:"Status",predicate:"status",sortable:true},
                    {text:"Action",predicate:"",sortable:false}
                ];

});


app.controller('eventEditCtrl', function ($scope, $modalInstance, item, Data) {

  $scope.event = angular.copy(item);
        
        $scope.cancel = function () {
            $modalInstance.dismiss('Close');
        };
        $scope.title = (item.event_id > 0) ? 'Edit Event' : 'Add Event';
        $scope.buttonText = (item.event_id > 0) ? 'Update Event' : 'Add New Event';

        var original = item;
        $scope.isClean = function() {
            return angular.equals(original, $scope.event);
        }
        $scope.saveProduct = function (event) {
            if(event.event_id > 0){
                Data.put('events/'+event.event_id, event).then(function (result) {
                    if(result.status != 'error'){
                        var x = angular.copy(event);
                        x.save = 'update';
                        $modalInstance.close(x);
                    }else{
                        $scope.msg = result.message;
                        console.log(result);
                    }
                });
            }else{
                event.status = 'Active';
                Data.post('events', event).then(function (result) {
                    if(result.status != 'error'){
                        var x = angular.copy(event);
                        x.save = 'insert';
                        x.event_id = result.data;
                        $modalInstance.close(x);
                    }else{
                        console.log(result);
                        $scope.msg = result.message;
                    }
                });
            }
        };
});
