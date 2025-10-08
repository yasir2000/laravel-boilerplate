package com.hrboilerplate.integration.service;

import org.springframework.stereotype.Service;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * Service for sending notifications.
 */
@Service("notificationService")
public class NotificationService {
    
    private static final Logger logger = LoggerFactory.getLogger(NotificationService.class);
    
    public void notifyAdmin(Object message) {
        logger.warn("Admin notification: {}", message);
        // TODO: Implement admin notification logic
    }
}