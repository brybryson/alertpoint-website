import React, { useState, useEffect, useRef } from 'react';

const EvacuationPlan = () => {
  const [currentTime, setCurrentTime] = useState('');
  const [emergencyStatus, setEmergencyStatus] = useState('NORMAL');
  const [routeFilters, setRouteFilters] = useState({
    primary: true,
    secondary: true,
    emergency: false
  });
  const [stats, setStats] = useState({
    totalPopulation: 12450,
    evacuationCenters: 5,
    activeRoutes: 8,
    evacuationTime: '45 min'
  });

  const mapRef = useRef(null);
  const mapInstanceRef = useRef(null);
  const routesRef = useRef([]);
  const centersRef = useRef([]);

  // Evacuation centers data
  const evacuationCenters = [
    {name: "Barangay 170 Gymnasium", lat: 14.6770, lng: 121.0440, capacity: 500, status: "Active"},
    {name: "Elementary School", lat: 14.6750, lng: 121.0450, capacity: 300, status: "Active"},
    {name: "Community Center", lat: 14.6780, lng: 121.0430, capacity: 200, status: "Active"},
    {name: "Church Hall", lat: 14.6760, lng: 121.0445, capacity: 150, status: "Active"},
    {name: "Basketball Court", lat: 14.6765, lng: 121.0435, capacity: 100, status: "Standby"}
  ];

  // Evacuation routes data
  const evacuationRoutes = [
    {
      name: "Route A - North Exit",
      coordinates: [[14.6760, 121.0437], [14.6770, 121.0440], [14.6780, 121.0430]],
      type: "primary",
      color: "#3B82F6"
    },
    {
      name: "Route B - South Exit", 
      coordinates: [[14.6760, 121.0437], [14.6750, 121.0450], [14.6765, 121.0435]],
      type: "primary",
      color: "#10B981"
    },
    {
      name: "Route C - East Exit",
      coordinates: [[14.6760, 121.0437], [14.6760, 121.0445], [14.6770, 121.0440]],
      type: "secondary",
      color: "#F59E0B"
    }
  ];

  // Update time every second
  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      const timeString = now.toLocaleTimeString('en-PH', {
        timeZone: 'Asia/Manila',
        hour12: true,
        hour: '2-digit',
        minute: '2-digit'
      });
      setCurrentTime(timeString);
    };

    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  }, []);

  // Initialize map
  useEffect(() => {
    if (!mapRef.current || mapInstanceRef.current) return;

    // Load Leaflet dynamically
    const loadMap = async () => {
      if (typeof window !== 'undefined' && window.L) {
        const map = window.L.map(mapRef.current).setView([14.6760, 121.0437], 15);

        // Add OpenStreetMap tiles
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        mapInstanceRef.current = map;

        // Add evacuation centers
        evacuationCenters.forEach((center) => {
          const marker = window.L.marker([center.lat, center.lng])
            .addTo(map)
            .bindPopup(`
              <b>${center.name}</b><br>
              Capacity: ${center.capacity} persons<br>
              Status: <span style="color: ${center.status === 'Active' ? '#10B981' : '#F59E0B'}">${center.status}</span>
            `);
          
          centersRef.current.push({...center, marker});
        });

        // Add evacuation routes
        evacuationRoutes.forEach(route => {
          const polyline = window.L.polyline(route.coordinates, {
            color: route.color,
            weight: 5,
            opacity: 0.8
          }).addTo(map).bindPopup(`<b>${route.name}</b><br>Type: ${route.type}`);
          
          routesRef.current.push({...route, polyline});
        });

        // Add current location marker
        window.L.marker([14.6760, 121.0437])
          .addTo(map)
          .bindPopup("<b>Current Location</b><br>Barangay 170, Caloocan City")
          .openPopup();
      }
    };

    // Load Leaflet CSS and JS if not already loaded
    if (!document.querySelector('link[href*="leaflet"]')) {
      const leafletCSS = document.createElement('link');
      leafletCSS.rel = 'stylesheet';
      leafletCSS.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css';
      document.head.appendChild(leafletCSS);
    }

    if (!window.L) {
      const leafletJS = document.createElement('script');
      leafletJS.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
      leafletJS.onload = loadMap;
      document.head.appendChild(leafletJS);
    } else {
      loadMap();
    }

    return () => {
      if (mapInstanceRef.current) {
        mapInstanceRef.current.remove();
        mapInstanceRef.current = null;
      }
    };
  }, []);

  // Map controls
  const handleZoomIn = () => {
    if (mapInstanceRef.current) mapInstanceRef.current.zoomIn();
  };

  const handleZoomOut = () => {
    if (mapInstanceRef.current) mapInstanceRef.current.zoomOut();
  };

  const handleResetView = () => {
    if (mapInstanceRef.current) mapInstanceRef.current.setView([14.6760, 121.0437], 15);
  };

  // Emergency activation
  const handleActivateEmergency = () => {
    setEmergencyStatus('EMERGENCY');
    alert('Emergency evacuation protocol activated. Mass alerts will be sent to all registered users.');
  };

  const handleTestEvacuation = () => {
    alert('Test evacuation initiated. This is a drill only.');
  };

  const handleSendAlerts = () => {
    alert('Mass alert sent to all registered mobile users.');
  };

  // Handle route filter changes
  const handleRouteFilterChange = (routeType) => {
    setRouteFilters(prev => ({
      ...prev,
      [routeType]: !prev[routeType]
    }));
  };

  // Handle center click
  const handleCenterClick = (center) => {
    if (mapInstanceRef.current) {
      mapInstanceRef.current.setView([center.lat, center.lng], 18);
      const centerData = centersRef.current.find(c => c.name === center.name);
      if (centerData && centerData.marker) {
        centerData.marker.openPopup();
      }
    }
  };

  // Emergency procedures
  const emergencyProcedures = [
    {
      step: 1,
      title: "Alert Reception",
      description: "Listen for evacuation sirens or mobile alerts",
      color: "red"
    },
    {
      step: 2,
      title: "Immediate Actions", 
      description: "Secure important documents, medications, and emergency supplies",
      color: "orange"
    },
    {
      step: 3,
      title: "Route Selection",
      description: "Follow designated evacuation routes shown on the map",
      color: "yellow"
    },
    {
      step: 4,
      title: "Safe Assembly",
      description: "Report to assigned evacuation center and register with officials",
      color: "green"
    }
  ];

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center">
              <i className="fas fa-map-marker-alt text-3xl text-blue-600 mr-3"></i>
              <div>
                <h1 className="text-xl font-bold text-gray-900">AlertPoint</h1>
                <p className="text-sm text-gray-600">Barangay 170, Caloocan City</p>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <div className="text-right">
                <p className="text-sm font-medium text-gray-900">{currentTime}</p>
                <p className="text-xs text-gray-500">Philippine Standard Time</p>
              </div>
              <i className="fas fa-cog text-gray-400 cursor-pointer hover:text-gray-600"></i>
            </div>
          </div>
        </div>
      </header>

      {/* Navigation */}
      <nav className="bg-white border-b">
        <div className="max-w-8xl mx-auto px-7 sm:px-6 lg:px-8">
          <div className="flex space-x-8">
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-gray-500 hover:text-gray-700">
              <i className="fas fa-chart-bar mr-2"></i>
              Dashboard
            </a>
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-gray-500 hover:text-gray-700">
              <i className="fas fa-bell mr-2"></i>
              Alerts
            </a>
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-gray-500 hover:text-gray-700">
              <i className="fas fa-chart-line mr-2"></i>
              Reports
            </a>
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-gray-500 hover:text-gray-700">
              <i className="fas fa-users mr-2"></i>
              Users
            </a>
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-blue-600 border-blue-500">
              <i className="fas fa-route mr-2"></i>
              Evacuation Plan
            </a>
            <a href="#" className="nav-tab flex items-center px-1 py-4 text-sm font-medium border-b-2 text-gray-500 hover:text-gray-700">
              <i className="fas fa-cog mr-2"></i>
              Settings
            </a>
          </div>
        </div>
      </nav>

      <main className="max-w-8xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-2 sm:px-0">
          
          {/* Page Header */}
          <div className="mb-6">
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Emergency Evacuation Plan</h1>
            <p className="text-gray-600">Interactive evacuation routes and emergency procedures for Barangay 170, Caloocan City</p>
          </div>

          {/* Emergency Status Bar */}
          <div className={`${emergencyStatus === 'NORMAL' ? 'bg-green-50 border-green-400' : 'bg-red-50 border-red-400'} border-l-4 p-4 rounded-lg mb-6`}>
            <div className="flex items-center">
              <i className={`fas ${emergencyStatus === 'NORMAL' ? 'fa-shield-alt text-green-400' : 'fa-exclamation-triangle text-red-400 animate-pulse'} mr-3 text-2xl`}></i>
              <div>
                <h3 className={`text-sm font-medium ${emergencyStatus === 'NORMAL' ? 'text-green-800' : 'text-red-800'}`}>
                  Current Status: {emergencyStatus}
                </h3>
                <p className={`text-sm ${emergencyStatus === 'NORMAL' ? 'text-green-700' : 'text-red-700'}`}>
                  {emergencyStatus === 'NORMAL' 
                    ? 'No active emergencies. All evacuation routes are clear and accessible.'
                    : 'All evacuation routes are now active. Residents should proceed to nearest evacuation center.'
                  }
                </p>
              </div>
            </div>
          </div>

          {/* Control Panel */}
          <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            {/* Quick Actions */}
            <div className="lg:col-span-1">
              <div className="bg-white rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div className="space-y-3">
                  <button 
                    onClick={handleActivateEmergency}
                    className="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                  >
                    <i className="fas fa-exclamation-triangle mr-2"></i>
                    Activate Emergency
                  </button>
                  <button 
                    onClick={handleTestEvacuation}
                    className="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors"
                  >
                    <i className="fas fa-play mr-2"></i>
                    Test Evacuation
                  </button>
                  <button 
                    onClick={handleSendAlerts}
                    className="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    <i className="fas fa-bullhorn mr-2"></i>
                    Send Mass Alert
                  </button>
                </div>
                
                <div className="mt-6">
                  <h4 className="text-md font-semibold text-gray-900 mb-2">Route Options</h4>
                  <div className="space-y-2">
                    {Object.entries(routeFilters).map(([key, value]) => (
                      <label key={key} className="flex items-center">
                        <input 
                          type="checkbox" 
                          checked={value}
                          onChange={() => handleRouteFilterChange(key)}
                          className="mr-2 text-blue-600"
                        />
                        <span className="text-sm capitalize">{key} Routes</span>
                      </label>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Interactive Map */}
            <div className="lg:col-span-3">
              <div className="bg-white rounded-lg shadow p-6">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold text-gray-900">Evacuation Map</h3>
                  <div className="flex space-x-2">
                    <button 
                      onClick={handleZoomIn}
                      className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
                    >
                      <i className="fas fa-plus"></i>
                    </button>
                    <button 
                      onClick={handleZoomOut}
                      className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"
                    >
                      <i className="fas fa-minus"></i>
                    </button>
                    <button 
                      onClick={handleResetView}
                      className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                      Reset View
                    </button>
                  </div>
                </div>
                <div ref={mapRef} className="w-full h-96 rounded-lg border"></div>
              </div>
            </div>
          </div>

          {/* Evacuation Statistics */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <i className="fas fa-users text-2xl text-blue-600"></i>
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">Total Population</p>
                  <p className="text-2xl font-bold text-gray-900">{stats.totalPopulation.toLocaleString()}</p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <i className="fas fa-home text-2xl text-green-600"></i>
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">Evacuation Centers</p>
                  <p className="text-2xl font-bold text-gray-900">{stats.evacuationCenters}</p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <i className="fas fa-route text-2xl text-orange-600"></i>
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">Active Routes</p>
                  <p className="text-2xl font-bold text-gray-900">{stats.activeRoutes}</p>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <i className="fas fa-clock text-2xl text-red-600"></i>
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">Est. Evacuation Time</p>
                  <p className="text-2xl font-bold text-gray-900">{stats.evacuationTime}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Evacuation Centers and Procedures */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Evacuation Centers List */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Evacuation Centers</h3>
              <div className="space-y-4">
                {evacuationCenters.map((center, index) => (
                  <div 
                    key={index}
                    onClick={() => handleCenterClick(center)}
                    className="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <h4 className="font-semibold text-gray-900">{center.name}</h4>
                        <p className="text-sm text-gray-600">Capacity: {center.capacity} persons</p>
                        <p className={`text-xs font-medium ${center.status === 'Active' ? 'text-green-600' : 'text-yellow-600'}`}>
                          {center.status}
                        </p>
                      </div>
                      <i className="fas fa-map-marker-alt text-blue-600"></i>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Emergency Procedures */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Emergency Procedures</h3>
              <div className="space-y-4">
                {emergencyProcedures.map((procedure) => (
                  <div key={procedure.step} className="flex items-start">
                    <div className={`flex-shrink-0 w-8 h-8 bg-${procedure.color}-100 rounded-full flex items-center justify-center mr-3`}>
                      <span className={`text-${procedure.color}-600 font-bold text-sm`}>{procedure.step}</span>
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900">{procedure.title}</h4>
                      <p className="text-sm text-gray-600">{procedure.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

        </div>
      </main>

      <style jsx>{`
        .nav-tab:hover {
          color: #374151;
          border-bottom-color: #d1d5db;
        }

        @keyframes pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.5; }
        }

        .animate-pulse {
          animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
      `}</style>
    </div>
  );
};

export default EvacuationPlan;