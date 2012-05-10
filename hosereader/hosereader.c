/*
 * hosereader.c
 *
 *  Created on: 2010-11-27
 *      Author: dennis
 */

#include <stdio.h>
#include <curl/curl.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include "hiredis.h"

#define TWITTER_STREAM "https://stream.twitter.com/1/statuses/sample.json"
#define DEFAULT_REDIS_HOST "localhost"
#define DEFAULT_REDIS_PORT 6379
#define REDIS_QUEUE_KEY "tweetqueue"
#define DEFAULT_TWITTER_USERNAME "username"
#define DEFAULT_TWITTER_PASSWORD "password"

char* redisHost = DEFAULT_REDIS_HOST;
int redisPort = DEFAULT_REDIS_PORT;
char* buffer;
char* twitterUsername = DEFAULT_TWITTER_USERNAME;
char* twitterPassword = DEFAULT_TWITTER_PASSWORD;
redisContext *rh;

size_t parseHose( void *ptr, size_t size, size_t nmemb, void *userdata)
{
	// Get the string from the pointer according to the size.
	int inStringSize = size*(nmemb+1);
	char* inString = (char*) malloc(inStringSize);
	if(!inString)
	{
		printf("Could not allocate %d bytes in memory for incoming string", inStringSize);
		return 0;
	}
	memcpy(inString, ptr, size*nmemb);
	inString[size*nmemb] = '\0';
	//printf("Read: %s\n", inString);

	// Copy inString into buffer
	int curBufferSize = sizeof(char)*(strlen(buffer) + 1);
	int newBufferSize = curBufferSize + inStringSize - sizeof(char);
	//printf("New Size of buffer: %d\n", newBufferSize);

	printf("Reallocating %d bytes for resized buffer\n", newBufferSize);
	printf("Current buffer: %s\n", buffer);

	char* newBuffer = (char*) malloc(newBufferSize);
	if(!newBuffer)
	{
		printf("Could not allocate %d bytes in memory for new buffer\n", newBufferSize);
		return 0;
	}
	memset(newBuffer, 0, newBufferSize);
	memcpy(newBuffer, buffer, curBufferSize);
	strcat(newBuffer, inString);
	free(buffer);
	buffer = newBuffer;
	
//	buffer = (char*) realloc(buffer, newBufferSize);
//	if(!buffer)
//	{
//		printf("Could not reallocate %d bytes in memory for new buffer", newBufferSize);
//		return 0;
//	}
//	strcat(buffer, inString);

//	free(inString);

	//printf("Buffer: %s\n", buffer);

	char* start = buffer;
	char* loc = strchr(start, '\n');
	while(loc != NULL)
	{
		int length = loc-start;
		//printf("Tweet length: %d\n", length);
		int tweetSize = length * sizeof(char);
		char* tweet = (char*) malloc(tweetSize);
		if(!tweet)
		{
			printf("Could not allocate %d bytes in memory for new tweet", tweetSize);
			return 0;
		}
		strncpy(tweet, buffer, length);
		tweet[length-1] = '\0';


		printf("Tweet: %s\n", tweet);

		redisCommand(rh, "RPUSH %s %s", REDIS_QUEUE_KEY, tweet);

		free(tweet);
		start = loc+1;
		loc = strchr(start, '\n');
	}

	// Reduce buffer what we've already read.
	char* restOfTweets = strrchr(buffer, '\n');
	if(restOfTweets != NULL)
	{
		int restOfTweetsSize = sizeof(char)*strlen(restOfTweets); // -1 for \n at first character
		buffer = (char*) realloc(buffer, restOfTweetsSize);
		if(!buffer)
		{
			printf("Could not reallocate %d bytes in memory for new buffer for rest of tweets", restOfTweetsSize);
			return 0;
		}
		strcpy(buffer, restOfTweets+1);
	}

	//printf("New buffer: %s\n", buffer);

	return size*nmemb;
}


int main( int argc, char *argv[] )
{
	// Parse arguments
	opterr = 0;
	int c;
	while ((c = getopt(argc, argv, "h:P:u:p:")) != -1)
	{
		switch (c)
		{
			case 'h':
				redisHost = optarg;
				break;
			case 'P':
				redisPort = atoi(optarg);
				break;
			case 'u':
				twitterUsername = optarg;
				break;
			case 'p':
				twitterPassword = optarg;
				break;
			case '?':
				printf("Unknown option character `\\x%x'.\n", optopt);
				return 1;
			default:
				printf("Unknown error occurred while reading arguments.");
				return 1;
		}
	}

	// Initialize buffer:
	buffer = (char*) malloc(sizeof(char));
	memset(buffer, 0, 1);

	// Setup connection to Redis
	printf("Connecting to Redis host at %s:%d\n", redisHost, redisPort);
	rh = redisConnect(redisHost, redisPort);
	if(rh->err)
	{
		printf("Could not connect to Redis host at %s:%d\n", redisHost, redisPort);
		printf("Error: %s\n", rh->errstr);
		return -1;
	}

	CURL *curl;
	CURLcode res;

	curl = curl_easy_init();
	if(curl) {
		curl_easy_setopt(curl, CURLOPT_URL, TWITTER_STREAM);
		curl_easy_setopt(curl, CURLOPT_VERBOSE, 2);
		curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, parseHose);
		curl_easy_setopt(curl, CURLOPT_USERNAME, twitterUsername);
		curl_easy_setopt(curl, CURLOPT_PASSWORD, twitterPassword);
		res = curl_easy_perform(curl);
		/* always cleanup */
		curl_easy_cleanup(curl);
	}
	return 0;		// exit process function
}

