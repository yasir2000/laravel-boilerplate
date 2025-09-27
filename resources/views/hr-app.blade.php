<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HR Management System - Desktop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=10, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- ExtJS CSS - Using CDN for faster setup -->
    <link rel="stylesheet" type="text/css" href="https://cdn.sencha.com/ext/gpl/7.6.0/classic/theme-desktop/resources/theme-desktop-all.css">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- HR Application Custom Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('hr-app/styles/hr-styles.css') }}">
    
    <!-- ExtJS JavaScript - Using CDN -->
    <script type="text/javascript" src="https://cdn.sencha.com/ext/gpl/7.6.0/ext-all.js"></script>
    <script type="text/javascript" src="https://cdn.sencha.com/ext/gpl/7.6.0/classic/theme-desktop/theme-desktop.js"></script>
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #1e3a5f;
            overflow: hidden;
        }
        
        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 18px;
            z-index: 10000;
        }
        
        /* Custom Windows-like styling */
        .desktop-style {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8b 100%);
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div>Loading HR Management System...</div>
        <div style="margin-top: 10px; text-align: center;">
            <div style="display: inline-block; width: 200px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px;">
                <div style="width: 0%; height: 100%; background: #4CAF50; border-radius: 2px; animation: loading 2s ease-in-out infinite;"></div>
            </div>
        </div>
    </div>

    <style>
        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
    </style>

    <script type="text/javascript">
        Ext.onReady(function() {
            // Hide loading screen
            document.getElementById('loading').style.display = 'none';
            
            // Set theme to desktop for Windows-like appearance
            Ext.setTheme('desktop');
            
            // Enable state management
            Ext.state.Manager.setProvider(Ext.create('Ext.state.LocalStorageProvider'));
            
            // Create the application
            Ext.create('HRApp.Application');
        });
    </script>
    
    <!-- Application JavaScript files -->
    <script type="text/javascript" src="{{ asset('hr-app/app/Application.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/view/main/MainView.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/view/main/MainController.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/view/main/MainModel.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/model/Employee.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/model/Department.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/store/EmployeeStore.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/store/DepartmentStore.js') }}"></script>
    <script type="text/javascript" src="{{ asset('hr-app/app/view/employee/EmployeePanel.js') }}"></script>
</body>
</html>