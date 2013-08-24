$(function () {
    "use strict";
});

// make map available for easy debugging
var map,
    vectors,
    markers,
    p = [],
    changed = [],
    changedPolygons = [],
    wgs84 = new OpenLayers.Projection("EPSG:4326"),
    osgb = new OpenLayers.Projection("EPSG:27700"),
    positionUri,
    label = [],
    icons = [],
    iconCounts = [],
    lastevent,
    prevname = '',
    wkt = new OpenLayers.Format.WKT(),
    modify,
    drag;

// increase reload attemptscurl
OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;

function focusPoint(positionUri) {
    "use strict";
    var existingMarker = markers.getFeatureByFid(positionUri);
    if (existingMarker !== null) {
        map.panTo(new OpenLayers.LonLat(existingMarker.geometry.x, existingMarker.geometry.y));
    }
}

function selectIcon(uri) {
    "use strict";
    $('#icon')[0].value = uri;
    $('#selected-icon')[0].src = uri;
}

function drop(positionUri, pixel, requireUpdateFeature) {
    "use strict";
    if (positionUri === undefined) {
        return;
    }
    var lonlat = map.getLonLatFromViewPortPx(pixel),
        llc = lonlat.clone(),
        existingMarker,
        l;

    if (requireUpdateFeature) {
        existingMarker = markers.getFeatureByFid(positionUri);
        if (existingMarker !== null) {
            markers.removeFeatures(existingMarker);
        }

        p[positionUri] = new OpenLayers.Feature.Vector(
            new OpenLayers.Geometry.Point(llc.lon, llc.lat),
            positionUri,
            {
                externalGraphic: icons[positionUri],
                graphicWidth: 32,
                graphicHeight: 37,
                graphicXOffset: -16,
                graphicYOffset: -37,
                graphicTitle: label[positionUri],
                graphicOpacity: 0.7
            }
        );
        p[positionUri].fid = positionUri;
        markers.addFeatures(p[positionUri]);
    }
    changed[positionUri] = true;
    llc.transform(map.getProjectionObject(), wgs84);
    l = Math.round(llc.lat * 1000000) / 1000000 + '/' + Math.round(llc.lon * 1000000) / 1000000;
    document.getElementById('loc_' + positionUri).innerHTML = l;
    positionUri = undefined;
    document.getElementById('save_link').style.display = "block";
}

function save() {
    "use strict";
    var str = '',
        i = 0,
        llc,
        q;
    for (q in changed) {
        llc = p[q].geometry.clone();
        llc.transform(map.getProjectionObject(), wgs84);
        str += q + '|' + llc.y + '|' + llc.x + '|' + label[q] + '|' + icons[q] + '||';
        i += 1;
    }
    for (q in changedPolygons) {
        str += q + '|WKT|' + changedPolygons[q] + '||';
        i += 1;
    }
    OpenLayers.Request.POST({
        url : "http://opendatamap.ecs.soton.ac.uk/mymap/save.php?username=" + param_username + "&map=" + param_map,
        data : str,
        success : function (response) {
            var q;
            for (q in changed) {
                document.getElementById('loc_' + q).innerHTML += ' (OS)';
                markers.getFeatureByFid(q).style.graphicOpacity = 1.0;
            }
            markers.redraw();
            changed = [];
            changedPolygons = [];
            document.getElementById('save_link').style.display = 'none';
        },
        failure : function (response) {
            alert(response.responseText);
        }
    });
    return i;
}

function loadPolygons() {
    "use strict";
    OpenLayers.Request.GET({
        url : "http://opendatamap.ecs.soton.ac.uk/mymap/load-polygons.php?u=" + param_username + "&m=" + param_map,
        success : function (response) {
            var d = JSON.parse(response.responseText);
            var v;
            var vector;
            var vfeatures = [];
            for (v in d) {
                vector = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(d[v].wkt).transform(wgs84, osgb));
                vector.fid = d[v].uri;
                vfeatures.push(vector);
            }
            vectors.addFeatures(vfeatures);
            setBounds();
        },
        failure : function (response) {
            alert(response.responseText);
        }
    });
}

function processName() {
    "use strict";
    if (prevname !== $('#name')[0].value) {
        $('#uri')[0].value = $('#name')[0].value.toLowerCase().replace(/\W/g, '-');
    }
    prevname = $('#name')[0].value;
}

function newDialog(pixel) {
    "use strict";
    $('#name')[0].value = '';
    $('#uri')[0].value = '';
    $('#dialog-modal').dialog({
        width: '40em',
        modal: true,
        buttons: {
            Ok: function () {
                var uri = $('#uri')[0].value,
                    newli;
                if ($('#name')[0].value === '') {
                    alert('Title not set.');
                } else if (uri === '') {
                    alert('ID not set.');
                } else if (uri !== uri.toLowerCase().replace(/\W/g, '-')) {
                    alert('ID does not meet requirements.  Updating ID...');
                    $('#uri')[0].value = uri.toLowerCase().replace(/\W/g, '-');
                } else if ($('#icon')[0].value === '') {
                    alert('Icon not set.');
                } else if (p[uri] !== undefined) {
                    alert('Point with this ID already exists.  Please choose a new ID.');
                } else {
                    $(this).dialog("close");
                    icons[uri] = $('#icon')[0].value;
                    label[uri] = $('#name')[0].value;
                    newli = "<img class='draggable' style='z-index:1000; float:left; margin-right:5px' src='" + icons[uri] + "' />";
                    newli += label[uri] + "<br/><span class='small' id='loc_" + uri + "'>Location not set</span>";
                    newli = "<li id='" + uri + "' onclick=\"focusPoint('" + uri + "');\">" + newli + "</li>";
                    $("#points").append(newli);
                    $("#" + uri + " .draggable").draggable({
                        cursorAt: {cursor: "crosshair", top: 39, left: 17},
                        helper: function (event) {
                            lastevent = event;
                            return $("<img src='" + event.currentTarget.src + "' />");
                        },
                        revert: "invalid"
                    });
                    drop(uri, pixel, true);
                }
            }
        }
    });
}

function newPolygonDialog(pixel) {
    "use strict";
    $('#polygon-uri')[0].value = '';
    $('#polygondialog-modal').dialog({
        width: '40em',
        modal: true,
        buttons: {
            Ok: function () {
                var uri = $('#polygon-uri')[0].value;
                if (uri === '') {
                    alert('ID not set.');
                } else if (uri !== uri.toLowerCase().replace(/\W/g, '-')) {
                    alert('ID does not meet requirements.  Updating ID...');
                    $('#uri')[0].value = uri.toLowerCase().replace(/\W/g, '-');
                } else if (p[uri] !== undefined) {
                    alert('Point with this ID already exists.  Please choose a new ID.');
                } else {
                    $(this).dialog("close");
                    $("#" + uri + " .draggable").draggable({
                        cursorAt: {cursor: "crosshair", top: 39, left: 17},
                        helper: function (event) {
                            lastevent = event;
                            return $("<img src='" + event.currentTarget.src + "' />");
                        },
                        revert: "invalid"
                    });
                    newPolygon(uri, pixel);
                }
            }
        }
    });
}

function newPolygon(uri, pixel) {
    "use strict";
    var linearring,
        points = [],
        polygon,
        vector;
    map.getLonLatFromViewPortPx(pixel);
    points.push(new OpenLayers.Geometry.Point(map.getLonLatFromViewPortPx(pixel.add(10, 10)).lon, map.getLonLatFromViewPortPx(pixel.add(10, 10)).lat));
    points.push(new OpenLayers.Geometry.Point(map.getLonLatFromViewPortPx(pixel.add(10, -10)).lon, map.getLonLatFromViewPortPx(pixel.add(10, -10)).lat));
    points.push(new OpenLayers.Geometry.Point(map.getLonLatFromViewPortPx(pixel.add(-10, -10)).lon, map.getLonLatFromViewPortPx(pixel.add(-10, -10)).lat));
    points.push(new OpenLayers.Geometry.Point(map.getLonLatFromViewPortPx(pixel.add(-10, 10)).lon, map.getLonLatFromViewPortPx(pixel.add(-10, 10)).lat));
    linearring = new OpenLayers.Geometry.LinearRing(points);
    polygon = new OpenLayers.Geometry.Polygon([linearring]);
    vector = new OpenLayers.Feature.Vector(polygon);
    vector.fid = uri;
    vectors.addFeatures([vector]);
    changedPolygons[uri] = wkt.write(new OpenLayers.Feature.Vector(vector.geometry.clone().transform(osgb, wgs84)));
}

function report(event) {
    if (event.type == 'afterfeaturemodified') {
        changedPolygons[event.feature.fid] = wkt.write(new OpenLayers.Feature.Vector(event.feature.geometry.clone().transform(osgb, wgs84)));
        document.getElementById('save_link').style.display = "block";
    }
}

function setBounds() {
    "use strict";
    var bounds;
    if (!map.getCenter()) {
        if (markers.features.length === 0 && vectors.features.length === 0) {
            bounds = new OpenLayers.Bounds(-6.379880, 49.871159, 1.768960, 55.811741);
            bounds.transform(wgs84, map.getProjectionObject());
        } else if(markers.features.length === 0) {
            bounds = vectors.getDataExtent();
        } else if(vectors.features.length === 0) {
            bounds = markers.getDataExtent();
        } else {
            bounds = markers.getDataExtent().extend(vectors.getDataExtent());
        }
console.log(bounds);
        map.zoomToExtent(bounds);
    }
}

function init() {
    "use strict";
    var maxExtent = new OpenLayers.Bounds(0, 0, 700000, 1300000),
        restrictedExtent = maxExtent.clone(),
        maxResolution = 156543.0339,
        options,
        streetview;

    $(".draggable").draggable({
        cursorAt: {cursor: "crosshair", top: 39, left: 17},
        helper: function (event) {
            lastevent = event;
            return $("<img src='" + event.currentTarget.src + "' />");
        },
        revert: "invalid"
    });

    $('#icon-classes').tabs({
        ajaxOptions: {
            error: function (xhr, status, index, anchor) {
                $(anchor.hash).html("Failed");
            }
        }
    }).addClass('ui-tabs-vertical ui-helper-clearfix');
    $('#icon-classes li').removeClass('ui-corner-top').addClass('ui-corner-left');

    $("#map").droppable({
        drop: function (event, ui) {
            var id = lastevent.currentTarget.parentElement.id,
                pixel = new OpenLayers.Pixel(event.pageX - window.pageXOffset - 1, event.pageY - window.pageYOffset - 2);
            lastevent = event;
            if (id === '_new_') {
                newDialog(pixel);
            } else if (id === '_newpolygon_') {
                newPolygonDialog(pixel);
            } else {
                drop(id, pixel, true);
            }
            lastevent = event;
        }
    });

    options = {
        projection: osgb,
        displayProjection: wgs84,
        units: "m",
        numZoomLevels: 18,
        maxResolution: maxResolution,
        maxExtent: maxExtent,
        restrictedExtent: restrictedExtent,
        resolutions: [1763.889,352.778,176.389,88.194,35.278,26.458,17.639,8.819,3.528,1.764,0.882,0.441]
    };

    map = new OpenLayers.Map('map', options);

    streetview = new OpenLayers.Layer.WMS( 
        "Edina OS OpenData WMS","http://opendatamap.ecs.soton.ac.uk/mymap/wms.php",
        {}, 
        {attribution: "Contains Ordnance Survey data. (c) Crown copyright and database right 20XX. Data provided by Digimap OpenStream, an EDINA, University of Edinburgh Service."}
    );

    loadInfo();

    var features = [];
    loadFeatures(features);

    markers = new OpenLayers.Layer.Vector("Editable Markers");

    var renderer = OpenLayers.Layer.Vector.prototype.renderers;
    vectors = new OpenLayers.Layer.Vector("Vector Layer", {
        renderers: renderer
    });

    loadPolygons();

    vectors.events.on({
        "beforefeaturemodified": report,
        "featuremodified": report,
        "afterfeaturemodified": report,
        "vertexmodified": report,
        "sketchmodified": report,
        "sketchstarted": report,
        "sketchcomplete": report
    });

    map.addLayers([streetview, markers, vectors]);

    markers.addFeatures(features);

    drag = new OpenLayers.Control.DragFeature(markers, {
        onComplete : function (feature, pixel) {
            drop(feature.fid, pixel, false);
            feature.style.graphicOpacity = 0.7;
            markers.redraw();
        }
    });
    map.addControl(drag);

    modify = new OpenLayers.Control.ModifyFeature(vectors);
    map.addControl(modify);

    modify.mode = OpenLayers.Control.ModifyFeature.RESHAPE;
}

function enableDragPoints() {
    "use strict";
    modify.deactivate();
    drag.activate();
}

function enableDragPolygons() {
    "use strict";
    drag.deactivate();
    modify.activate();
}
