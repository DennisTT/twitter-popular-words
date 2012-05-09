CC = cc
CPPFLAGS = -g -O0 -Wall -I../credis-0.2.3
LDFLAGS = -lcurl ../credis-0.2.3/libcredis.a

all: hosereader

hosereader: hosereader.o
	$(CC) $< $(CPPFLAGS) $(LDFLAGS) -o "hosereader"
	
clean:
	$(RM) hosereader *.o *.mod
