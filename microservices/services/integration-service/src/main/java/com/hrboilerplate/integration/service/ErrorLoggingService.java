package com.hrboilerplate.integration.service;

import org.springframework.stereotype.Service;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * Service for error logging functionality.
 */
@Service("errorLoggingService")
public class ErrorLoggingService {
    
    private static final Logger logger = LoggerFactory.getLogger(ErrorLoggingService.class);
    
    public void logError(Object error) {
        logger.error("Logging error: {}", error);
        // TODO: Implement database logging or external logging service
    }
}